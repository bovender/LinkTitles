#!/bin/sh

# This is a slightly modified version of the installer script published at
# https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md
# It does not depend on `wget` and uses `curl` instead.

EXPECTED_CHECKSUM="$(curl -q https://composer.github.io/installer.sig)"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
then
    >&2 echo 'ERROR: Invalid installer checksum'
    rm composer-setup.php
    exit 1
fi

# May need to remove the explicit pinning of version 1 in the future
php composer-setup.php --quiet --1
RESULT=$?
rm composer-setup.php
exit $RESULT
