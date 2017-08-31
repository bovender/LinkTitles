LinkTitles
==========

[MediaWiki extension](https://www.mediawiki.org/wiki/Extension:LinkTitles) that
automatically adds links to words that match titles of existing pages.

Minimum requirements: MediaWiki 1.25, PHP 5.3.
Source code documentation can be found at the [Github project
pages](http://bovender.github.io/LinkTitles).


Overview
--------

The **LinkTitles** extension automatically adds links to existing page titles
that occur on a given page. This will automatically cross-reference your wiki
for you. The extension can operate in three ways that can be used independently:

1.  Whenever a page is edited and saved, the extension will look if any existing
page titles occur in the text, and automatically add links (`[[...]]]`) to the
corresponding pages.

2.  Links may also be added on the fly whenever a page is rendered for display.
Most of the time, MediaWiki will fetch previously rendered pages from cache upon
a page request, but whenever a page is refreshed, the LinkTitles extension can
add its page links. These links are not hard-coded in the Wiki text. The
original content will not be modified.

3.  Batch mode enables Wiki administrators to process all pages in a Wiki at
once. Batch processing can either be started from a special page, or from the
server's command line (see [below](#Batch_processing "wikilink")).


Versions
--------

This extension is [semantically versioned](http://semver.org). In short, this
means that the first version number (the 'major') only changes on substantial
changes. The second number (the 'minor') changes when features are added or
significantly improved. The third number (the 'patch level') changes when bugs
are fixed.

 Major | Date | Significant changes
-|-|-
 5 | 09-2017 | Major rewrite
 4 | 00-2016 | Change format of the extension for MediaWiki version 1.29

For more details, see the `NEWS` file in the repository for a user-friendly
changelog, or study the commit messages.


Table of contents
-----------------

1.  [Installation](#installation)
2.  [Usage](#usage)
3.  [Configuration](#configuration)
4.  [Development](#development)
  - [Testing](#testing)


Installation
------------

To obtain the extension, you can either download a compressed archive from the
[Github releases page](https://github.com/bovender/LinkTitles/releases): Choose
one of the 'Source code' archives and extract it in your Wiki's `extension`
folder. Note that these archives contain a folder that is named after the
release version, e.g. `LinkTitles-5.0.0`. You may want to rename the folder to
`LinkTitles`.

Alternatively (and preferred by the author), if you have [Git](https://git-scm.com),
you can pull the repository in the `extensions` folder.

To activate the extension, add the following to your `LocalSettings.php` file:

    wfLoadExtension( 'LinkTitles' );

Do not forget to adjust the [configuration](#configuration) to your needs.


Usage
-----

### Editing a page


### Preventing automatic linking after minor edits

If the 'minor edit' check box is marked when you save a page, the extension will
not operate.

### Viewing a page

### Including and excluding pages with Magic Words

Add the magic word `__NOAUTOLINKS__` to a page to prevent automatic linking of
page titles.

The presence of `__NOAUTOLINKTARGET__` prevents a page from being automatically
linked to from other pages.

### Enable or disable automatic linking for sections

To **exclude** a section on your page from automatic linking, wrap it in `<noautolinks>...</noautolinks>` tags.

To **include** a section on your page for automatic linking, wrap it in
`<autolinks>...</autolinks>` tags. Of course this only makes sense if both
`$wgLinkTitlesParseOnEdit` and `$wgLinkTitlesParseOnRender` are set to `false`
**or** if the page contains the `__NOAUTOLINKS__` magic word.


### Batch processing

The extension provides two methods to batch-process all pages in a Wiki: A
special page (i.e., graphical user interface) and a command-line maintenance
script.

#### Special:LinkTitles

The special page provides a simple web interface to trigger batch processing. To
avoid blocking the web server for too long, the page will frequently reload
itself (this can be controlled by the `$wgLinkTitlesSpecialPageReloadAfter`
configuration variable that sysops can set in the `LocalSettings.php` file).

For security reasons, by default only users in the 'sysop' group are allowed to
view the special page (otherwise unauthorized people could trigger a parsing of
your entire wiki). To allow other user groups to view the page as well, add a
line `$wgGroupPermissions ['`<groupname>`']['linktitles-batch']` `=` `true` to
`LocalSettings.php`.

#### Maintenance script

If you have access to a shell on the server that runs your wiki, and are allowed
to execute `/bin/php` on the command line, you can use the extension's
maintenance script. Unlike MediaWiki's built-in maintenance scripts, this
resides not in the `maintenance/` subdirectory but in the extension's own
directory (the one where you downloaded and extracted the files to).

To trigger parsing of all pages, issue:

    php linktitles-cli.php

You can interrupt the process by hitting `CTRL+C` at any time.

To continue parsing at a later time, make a note of the index number of the last
page that was processed (e.g., 37), and use the maintenance script with the
`--start` option (or short `-s`) to indicate the start index:

    php LinkTitles.cli.php -s 37

See all available options with:

    php LinkTitles.cli.php -s 37


### Namespace support



Configuration
--------------

### Linking when a page is edited and saved

    $wgLinkTitlesParseOnEdit = true;

Parse page content whenever it is edited and saved, unless 'minor edit' box is
checked. This is the default mode of operation. It has the disadvantage that
newly created pages won't be linked to from existing pages until those existing
pages are edited and saved.

### Linking when a page is rendered for display

    $wgLinkTitlesParseOnRender = false;

Parse page content when it is rendered for viewing. Unlike the "parse on edit"
mode of operation, this will *not* hard-code the links in the Wiki text. Thus,
if you edit a page that had links added to it during rendering, you will not see
the links in the Wiki markup. Note that MediaWiki caches rendered pages in the
database, so that pages rarely need to be rendered. Rendering is whenever a page
is viewed and saved. Therefore, whether you want to enable both parse-on-edit
and parse-on-render depends on whether you want to have links (`[[...]]`) added
to the Wiki markup. Please note that the extension will work on a fully built
page when this mode is enabled; therefore, it *will* add links to text
transcluded from templates, regardless of the configuration setting of
`LinkTitlesSkipTemplages`. &emdash; It is also possible to purge the page cache
and trigger rendering by adding `?action=purge` to the URL.

### Enabling case-insensitive linking (smart mode)

    $wgLinkTitlesSmartMode = true;

With smart mode enabled, the extension will first perform a case-sensitive
search for page titles in the current page; then it will search for occurrences
of the page titles in a case-insensitive way and add aliased ('piped') links.
Thus, if you have a page `MediaWiki Extensions`, but write `Mediawiki
extensions` (with a small 'e') in your text, LinkTitles would generate a link
`\[\[MediaWiki Extensions|Mediawiki extensions\]\]`, obviating the need to add
dummy pages for variants of page titles with different cases.

Smart mode is enabled by default. You can disable it to increase performance of
the extension.


### Dealing with custom namespaces

<!-- TODO -->

### Linking or skipping headings

    $wgLinkTitlesParseHeadings = false;

Determines whether or not to add links to headings. By default, the extension
will leave your (sub)headings untouched. Only applies to parse-on-edit!

There is a known issue that the extension regards incorrectly formatted headings
as headings. Consider this line:

    ## incorrect heading #

This line is not recognized as a heading by MediaWiki because the pound signs
(`#`) are not balanced. However, the LinkTitles extension will currently treat
this line as a heading (if it starts and ends with pound signs).

### Prioritizing pages with short titles

    $wgLinkTitlesPreferShortTitles = false;

If `$wgLinkTitlesPreferShortTitles` is set to `true`, parsing will begin with
shorter page titles. By default, the extension will attempt to link the longest
page titles first, as these generally tend to be more specific.

### Filtering pages by title length

    $wgLinkTitlesMinimumTitleLength = 3;

Only link to page titles that have a certain minimum length. In my experience,
very short titles can be ambiguous. For example, "mg" may be "milligrams" on a
page, but there may be a page title "Mg" which redirects to the page
"Magnesium". This settings prevents erroneous linking to very short titles by
setting a minimum length. You can adjust this setting to your liking.

### Excluding pages from being linked to

    $wgLinkTitlesBlackList = [];

Exclude page titles in the array from automatic linking. You can populate this
array with common words that happen to be page titles in your Wiki. For example,
if for whatever reason you had a page "And" in your Wiki, every occurrence of
the word "and" would be linked to this page.

To add page titles to the black list, you can use statements such as

    $wgLinkTitlesBlackList[] = 'Some special page title';

in your `LocalSettings.php` file. Use one of these for every page title that you want to
put on the black list. Alternatively, you can specify the entire array:

    $wgLinkTitlesBlackList[] = [ 'Some special page title', 'Another one' ];

Keep in mind that a MediaWiki page title always starts with a capital letter
unless you have `$wgCapitalLinks = false;` in your `LocalSettings.php`.
**Therefore, if you have lowercase first letters in the black list array, they
will have no effect.**

### Dealing with templates

    $wgLinkTitlesSkipTemplates = false;

If set to true, do not parse the variable text of templates, i.e. in `{{my`
`template|some` `variable=some` `content}}`, leave the entire text between the
curly brackets untouched. If set to false (default setting), the text after the
pipe symbole ("|") will be parsed.

Note: This setting works only with parse-on-edit; it does not affect
parse-on-render!

### Multiple links to the same page

    $wgLinkTitlesFirstOnly = false;

If set to true, only link the first occurrence of a title on a given page. If
a link is piped, i.e. hiding the title of the target page:

    [[target page|text that appears as link text]]

then the LinkTitles extension does not count that as an occurrence.

### Partial words

    $wgLinkTitlesWordStartOnly = true;
    $wgLinkTitlesWordEndOnly = true;

Restrict linking to occurrences of the page titles at the start of a word. If
you want to have only the exact page titles linked, you need to set **both**
options `$wgLinkTitlesWordStartOnly` and `$wgLinkTitlesWordEndOnly` to *true*.
On the other hand, if you want to have all occurrences of a page title linked,
even if they are in the middle of a word, you need to set both options to
*false*.

Keep in mind that linking in MediaWiki is generally *case-sensitive*.

### Special page configuration

    wgLinkTitlesSpecialPageReloadAfter


Development
-----------

If you wish to contribute, please issue pull requests against the `develop`
branch, as I follow Vincent Driessen's advice on [A successful Git branching
model](http://nvie.com/git-model) (knowing that there are [alternative
workflows](http://scottchacon.com/2011/08/31/github-flow.html)).

The `master` branch contains stable releases only, so it is safe to pull the
master branch if you want to install the extension for your own wiki.


### Contributors

- Daniel Kraus (@bovender), main developer
- Ulrich Strauss (@c0nnex), namespaces
- Brent Laabs (@labster), code review and bug fixes
- @tetsuya-zama, bug fix


### Testing

Starting from version 5, LinkTitles finally comes with phpunit tests. The code is
not 100% covered yet. If you find something does not work as expected, let me
know and I will try to add unit tests and fix it.

Here's how I set up the testing environment. This may not be the canonical way
to do it. Basic information on testing MediaWiki can be found [here](https://www.mediawiki.org/wiki/Manual:PHP_unit_testing).

The following assumes that you have an instance of MediaWiki running locally
on your development machine. This assumes that you are running Linux (I personally
use Ubuntu).

1.  Pull the MediaWiki repository:

        cd ~/Code
        git clone --depth 1 https://phabricator.wikimedia.org/source/mediawiki.git

2.  Install [composer](https://getcomposer.org) locally and fetch the
dependencies (including development dependencies):

  Follow the instructions on the [composer download page](https://getcomposer.org/download),
  but instead of running `php composer-setup.php`, run:

        php composer-setup.php --install-dir=bin --filename=composer
        bin/composer install

3.  Install phpunit (it was already installed on my Ubuntu system when I began
testing LinkTitles, so I leave it up to you to figure out how to do it).

4.  Copy your `LocalSettings.php` over from your local MediaWiki installation
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


License
-------

Copyright 2012-2017 Daniel Kraus <mailto:bovender@bovender.de> (@bovender)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
MA 02110-1301, USA.
