## Usage of This Script (Auto Renew SSL Certificate)
This is a **generic** SSL Certificate Renewal Script that can be used for any domain for 365 days. To automatically renew SSL certificate via OpenSSL

Make sure your server has OpenSSL installed. If not install it by ``sudo apt-get install openssl``
1. Upload this script to server. Any preferred location, for Kimai time tracker, it's located at 

    ``var/plugins/LhgTrackerBundle/scripts/renew-ssl.sh``

2. Make the script executable: 

    ``chmod +x renew-ssl.sh``

3. Open the crontab file for editing: 

    ``crontab -e``

4. Add a new line to the crontab file that specifies when the script should run. For example, to run the script once a month at midnight, add the following line: 

    ``0 0 1 * * /path/to/renew-ssl.sh sub.domain.com``

    Here replace *sub.domain.com* by your dimain name. For Kimai Time Tracker it would be: 
    
    ``0 0 1 * * path/to/kimai/var/plugins/LhgTrackerBundle/scripts/renew-ssl.sh time.cloud.lhgdev.com``