LinkTitles
==========

MediaWiki extension that automatically adds links to words that match titles of existing pages.

For more information, see http://www.mediawiki.org/wiki/Extension:LinkTitles

Minimum requirements: MediaWiki 1.25, PHP 5.3

Source code documentation can be found at the [Github project
pages](http://bovender.github.io/LinkTitles).

This extension is [semantically versioned](http://semver.org).


Contributing
------------

If you wish to contribute, please issue pull requests against the `develop`
branch, as I follow Vincent Driessen's advice on [A successful Git branching
model](http://nvie.com/git-model) (knowing that there are [alternative
workflows](http://scottchacon.com/2011/08/31/github-flow.html)).

The `master` branch contains stable releases only, so it is safe to pull the
master branch if you want to install the extension for your own wiki.


Contributors
------------

- Daniel Kraus (@bovender), main developer
- Ulrich Strauss (@c0nnex), namespaces
- Brent Laabs (@labster), code review and bug fixes
- @tetsuya-zama, bug fix


Testing
-------

Starting from version 4.2.0, LinkTitles finally comes with phpunit tests.

Here's how I set up the testing environment. This may not be the canonical way
to do it. Basic information on testing MediaWiki can be found [here](https://www.mediawiki.org/wiki/Manual:PHP_unit_testing).

The following assumes that you have an instance of MediaWiki running locally
on your development machine. This assumes that you are running Linux (I personally
use Ubuntu).

1. Pull the MediaWiki repository:

        cd ~/Code
        git clone --depth 1 https://phabricator.wikimedia.org/source/mediawiki.git

2. Install [composer](https://getcomposer.org) locally and fetch the
  dependencies (including development dependencies):

  Follow the instructions on the [composer download page](https://getcomposer.org/download),
  but instead of running `php composer-setup.php`, run:

        php composer-setup.php --install-dir=bin --filename=composer
        bin/composer install

3. Install phpunit (it was already installed on my Ubuntu system when I began
  testing LinkTitles, so I leave it up to you to figure out how to do it).

4. Copy your `LocalSettings.php` over from your local MediaWiki installation
  and remove (or comment out) any lines that reference extensions or skins that
  you are not going to install to your test environment. For the purposes of
  testing the LinkTitles extension, leave the following line in place:

        wfLoadExtensions( array( 'LinkTitles' ));

  And ensure the settings file contains the following:

        $wgShowDBErrorBacktrace = true;

5. Create a symbolic link to your copy of the LinkTitles repository:

        cd ~/Code/mediawiki/extensions
        ln -s ~/Code/LinkTitles

6. Make sure your local MediaWiki instance is up to date. Otherwise phpunit may
  fail and tell you about database problems.

  This is because the local database is used as a template for the unit tests.
  For example, I initially had MW 1.26 installed on my laptop, but the cloned
  repository was MW 1.29.1. It's probably also possible to clone the repository
  with a specific version tag which matches your local installation.

7. Run the tests:

        cd ~/Code/mediawiki/tests/phpunit
        php phpunit.php --group bovender

  This will run all tests from the 'bovender' group, i.e. tests for my extensions.
  If you linked just the LinkTitles extension in step 5, only this extension
  will be tested.
