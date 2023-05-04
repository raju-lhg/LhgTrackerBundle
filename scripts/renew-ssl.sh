#!/bin/bash

# Check if domain name parameter was given
if [ $# -eq 0 ]; then
  echo "Please provide the domain name as a parameter."
  exit 1
fi

# Set domain name as a parameter
domain="$1"

# Generate new certificates
openssl req -new -newkey rsa:2048 -nodes -keyout /etc/ssl/private/"$domain".key -out /etc/ssl/certs/"$domain".csr
openssl x509 -req -days 365 -in /etc/ssl/certs/"$domain".csr -signkey /etc/ssl/private/"$domain".key -out /etc/ssl/certs/"$domain".crt

# Remove previous SSL configuration from Nginx config
grep -q '# SSL certificates' /etc/nginx/nginx.conf && sed -i '/# SSL certificates/,/^$/d' /etc/nginx/nginx.conf

# Add new SSL certificate location to Nginx config

#For Subdomain
sed -i "/server_name ${domain%.*}/a \ \n# SSL certificates\n    ssl_certificate     /etc/ssl/certs/$domain.crt;\n    ssl_certificate_key /etc/ssl/private/$domain.key;\n" /etc/nginx/nginx.conf

#For Main Domain
#sed -i "/server {/a \ \n# SSL certificates\n    ssl_certificate     /etc/ssl/certs/$domain.crt;\n    ssl_certificate_key /etc/ssl/private/$domain.key;\n" /etc/nginx/nginx.conf


# Reload Nginx configuration
systemctl reload nginx
