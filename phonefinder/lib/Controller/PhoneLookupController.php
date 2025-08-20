<?php

namespace OCA\PhoneFinder\Controller;

use OCP\AppFramework\OCSController;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Attribute\AdminRequired;       // PHP 8 attribute
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;      // optional for GET
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Accounts\IAccountManager;
use OCP\IPhoneNumberUtil;
use OCP\IGroupManager;
use OCP\IUser;

class PhoneLookupController extends OCSController {
    public function __construct(
        string $appName,
        IRequest $request,
        private IUserManager $userManager,
        private IAccountManager $accountManager,
        private IPhoneNumberUtil $phoneUtil,
        private IGroupManager $groupManager
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * GET /ocs/v2.php/apps/phonefinder/api/v1/users/by-phone?number=+15551234567&region=US
     */
    #[AdminRequired]        // let NC enforce admin automatically
    #[NoCSRFRequired]       // GET endpoint; avoids CSRF middleware noise
    public function byPhone(string $number = '', ?string $region = null): DataResponse {
        if ($number === '') {
            return new DataResponse(['message' => 'Missing number'], 400);
        }

        // Normalize to E.164
        try {
            $e164 = $this->phoneUtil->convertToStandardFormat($number, $region);
        } catch (\Throwable $e) {
            return new DataResponse(['message' => 'Invalid phone number'], 400);
        }

        $matches = [];

        // NOTE: closure receives an IUser object, not a UID string
        $this->userManager->callForAllUsers(function (IUser $u) use (&$matches, $e164) {
            $account = $this->accountManager->getAccount($u);
            $prop = $account->getProperty(\OCP\Accounts\IAccountManager::PROPERTY_PHONE);
            if ($prop === null) return;

            $raw = (string) $prop->getValue();
            if ($raw === '') return;

            try {
                $valE164 = $this->phoneUtil->convertToStandardFormat($raw, null);
            } catch (\Throwable $e) {
                return;
            }

            if ($valE164 === $e164) {
                $matches[] = [
                    'uid'         => $u->getUID(),
                    'displayname' => $u->getDisplayName(),
                    'phone'       => $valE164,
                    'email'       => $u->getEMailAddress(),
                ];
            }
        });

        return new DataResponse(['matches' => $matches, 'count' => \count($matches)], 200);
    }
}
