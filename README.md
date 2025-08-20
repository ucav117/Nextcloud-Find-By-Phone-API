# Nextcloud-Lookup-User-By-Phone-Number-API
Custom API endpoint to lookup users by their phone number.

Test script:

curl -u $USERNAME:$APP_PASSWORD \
 -H "OCS-APIRequest: true" \
 "https://your.nc/ocs/v2.php/apps/phonefinder/api/v1/users/by-phone?number=%2B15551234567&region=US"
