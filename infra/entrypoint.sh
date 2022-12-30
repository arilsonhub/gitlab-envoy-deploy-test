#!/usr/bin/env bash

setfacl -R -m u:deployer:rwx /opt/product
chfn -o umask=022 deployer
chmod g+s /opt/product
chown deployer:www-data -R /opt/product
chown www-data:www-data -R /opt/product/storage
/usr/sbin/sshd &
apache2-foreground &
su deployer
> /home/deployer/.ssh/known_hosts
ssh-keyscan -H gitlab-server >> /home/deployer/.ssh/known_hosts
ssh-keyscan -H product-stage >> /home/deployer/.ssh/known_hosts
tail -f /dev/null