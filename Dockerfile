# This Dockerfile can be used to create a Docker image/container
# that runs the unit tests on the LinkTitles extension.
FROM mediawiki:1.37
LABEL "MAINTAINER" Daniel Kraus (https://www.bovender.de)
RUN apt-get update -yqq && \
	apt-get install -yqq \
	php7.4-sqlite \
	sqlite3 \
	unzip \
	zip

WORKDIR /var/www/html
ADD install-composer.sh install-composer.sh
RUN chmod +x install-composer.sh
RUN ./install-composer.sh

COPY . /var/www/html/extensions/LinkTitles/
RUN mkdir /data && chown www-data /data

RUN php composer.phar update

WORKDIR /var/www/html/maintenance
RUN php install.php --pass linktitles --dbtype sqlite --extensions LinkTitles Tests admin

WORKDIR /var/www/html/tests/phpunit
CMD ["php", "phpunit.php", "--group", "bovender"]
