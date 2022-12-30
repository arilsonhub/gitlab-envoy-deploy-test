FROM php:8.1.2-apache

##################### USER CREATION #######################
RUN useradd -ms /bin/bash deployer \
  && usermod -a -G www-data deployer \
  && mkdir -p /home/deployer/.ssh \
  && chmod 700 /home/deployer/.ssh \
  && chown deployer:deployer /home/deployer/.ssh

WORKDIR /home/deployer/product

################### GENERAL CONFIG ########################
RUN apt-get update \      
  && export DEBIAN_FRONTEND=noninteractive \
  && apt-get install --assume-yes git \
  && apt-get install acl \
  && apt-get install -y tzdata \
  && ln -fs /usr/share/zoneinfo/America/Sao_Paulo /etc/localtime \
  && dpkg-reconfigure --frontend noninteractive tzdata \
  && apt-get install curl -y \
  && apt-get install --yes --no-install-recommends libpq-dev \
  && apt-get install --yes libpng-dev \
  && apt-get install --yes zlib1g-dev \
  && apt-get install --yes libzip-dev \
  && apt-get install --yes zip \
  && echo "post_max_size=300M" | tee -a /usr/local/etc/php/conf.d/uploads.ini > /dev/null \
  && echo "upload_max_filesize=50M" | tee -a /usr/local/etc/php/conf.d/uploads.ini > /dev/null \
  && echo "max_file_uploads=100" | tee -a /usr/local/etc/php/conf.d/uploads.ini > /dev/null

################### FRONT-END CONFIG ######################
RUN curl -fsSL https://deb.nodesource.com/setup_18.x \
  && apt-get install --yes nodejs \
  && apt-get install --yes npm

############### PROJECT HOME CREATION #####################
RUN mkdir -p /opt/product/current \
  && mkdir -p /opt/product/releases \
  && mkdir -p /opt/product/storage \
  && mkdir -p /opt/product/common/api \
  && mkdir -p /opt/product/common/web

################## COPY SCRIPTS ###########################
COPY ./infra/entrypoint.sh /usr/local/bin

###################### COMPOSER ###########################
RUN curl -o /tmp/composer-setup.php https://getcomposer.org/installer \
  && curl -o /tmp/composer-setup.sig https://composer.github.io/installer.sig \
  && php -r "if (hash('SHA384', file_get_contents('/tmp/composer-setup.php')) !== trim(file_get_contents('/tmp/composer-setup.sig'))) { unlink('/tmp/composer-setup.php'); echo 'Invalid installer' . PHP_EOL; exit(1); }" \
  && php /tmp/composer-setup.php --no-ansi --install-dir=/usr/local/bin --filename=composer --version=2.2.6 \
  && rm -f /tmp/composer-setup.*

###################### APACHE Config ######################
RUN a2enmod rewrite \
  && a2enmod remoteip \
  && mkdir -p /var/www/html1/public \
  && chmod -R 755 /var/www/html1 \
  && chown -R deployer:www-data /var/www/html1

###################### PHP Config ########################
RUN printf '[PHP]\ndate.timezone = "America/Sao_Paulo"\n' > /usr/local/etc/php/conf.d/tzone.ini

################### PHP EXTENSIONS ########################
RUN docker-php-ext-install pdo_pgsql \
  && docker-php-ext-install zip \
  && docker-php-ext-install bcmath \
  && docker-php-ext-install gd

##################### SSH CONFIG #########################
RUN apt-get install -y openssh-server \
  && mkdir /var/run/sshd \
  && sed 's@session\s*required\s*pam_loginuid.so@session optional pam_loginuid.so@g' -i /etc/pam.d/sshd
EXPOSE 22

ENTRYPOINT [ "/usr/local/bin/entrypoint.sh" ]