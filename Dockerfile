FROM php:7.3-apache
RUN docker-php-ext-install mysqli \
pdo pdo_mysql

RUN  apt-get update \
&& apt-get install -y libzip-dev \
&& apt-get install -y zlib1g-dev \
&& apt-get install -y sendmail \
&& apt-get install -y libpng-dev \
&& rm -rf /var/lib/apt/lists/* \
&& docker-php-ext-install zip \
&& docker-php-ext-install gd

RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

WORKDIR /var/www/html

RUN echo "sendmail_path=/usr/sbin/sendmail -t -i" >> /usr/local/etc/php/conf.d/sendmail.ini
RUN sed -i '/#!\/bin\/sh/aservice sendmail restart' /usr/local/bin/docker-php-entrypoint
RUN sed -i '/#!\/bin\/sh/aecho "$(hostname -i)\t$(hostname) $(hostname).localhost" >> /etc/hosts' /usr/local/bin/docker-php-entrypoint


COPY www/symfony.conf /etc/apache2/sites-available/symfony.conf
RUN a2dissite 000-default.conf
RUN a2ensite symfony.conf
RUN rm -rf /var/lib/apt/lists/*
RUN export PATH="$HOME/.symfony/bin:$PATH"
