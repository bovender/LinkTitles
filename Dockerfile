# This Dockerfile can be used to create a Docker image/container
# that runs the unit tests on the LinkTitles extension.
FROM mediawiki:1.34
MAINTAINER Daniel Kraus (https://www.bovender.de)
RUN apt update -yqq && \
    apt install -yqq \
	php7.0-sqlite \
	sqlite3 \
	unzip \
	zip
RUN curl https://raw.githubusercontent.com/composer/getcomposer.org/cb19f2aa3aeaa2006c0cd69a7ef011eb31463067/web/installer -s | php -- --quiet
RUN php composer.phar install

COPY . /var/www/html/extensions/LinkTitles/
RUN mkdir /data && chown www-data /data

WORKDIR /var/www/html/maintenance
RUN php install.php --pass admin --dbtype sqlite --extensions LinkTitles Tests admin

WORKDIR /var/www/html/tests/phpunit
CMD ["php", "phpunit.php", "--group", "bovender"]
