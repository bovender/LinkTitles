LinkTitles
==========

[MediaWiki extension](https://www.mediawiki.org/wiki/Extension:LinkTitles) that
automatically adds links to words that match titles of existing pages.

Minimum requirements: MediaWiki 1.25, PHP 5.3. Source code documentation can be
found at the [Github project pages](https://bovender.github.io/LinkTitles).


Table of contents
-----------------

1.  [Oveview](#overview)
    -   [Versions](#versions)
2.  [Installation](#installation)
3.  [Usage](#usage)
    -   [Editing a page](#editing-a-page)
    -   [Preventing automatic linking after minor edits](#preventing-automatic-linking-after-minor-edits)
    -   [Viewing a page](#viewing-a-page)
    -   [Including and excluding pages with Magic Words](#including-and-excluding-pages-with-magic-words)
    -   [Enable or disable automatic linking for sections](#enable-or-disable-automatic-linking-for-sections)
    -   [Namespace support](#namespace-support)
    -   [Batch processing](#batch-processing)
    -   [Special:LinkTitles](#special-linktitles)
    -   [Maintenance script](#maintenance-script)
4.  [Configuration](#configuration)
    -   [Linking when a page is edited and saved](#linking-when-a-page-is-edited-and-saved)
    -   [Linking when a page is rendered for display](#linking-when-a-page-is-rendered-for-display)
    -   [Enabling case-insensitive linking (smart mode)](#enabling-case-insensitive-linking-(smart-mode))
    -   [Dealing with custom namespaces](#dealing-with-custom-namespaces)
    -   [Linking or skipping headings](#linking-or-skipping-headings)
    -   [Prioritizing pages with short titles](#prioritizing-pages-with-short-titles)
    -   [Filtering pages by title length](#filtering-pages-by-title-length)
    -   [Excluding pages from being linked to](#excluding-pages-from-being-linked-to)
    -   [Dealing with templates](#dealing-with-templates)
    -   [Multiple links to the same page](#multiple-links-to-the-same-page)
    -   [Partial words](#partial-words)
    -   [Special page configuration](#special-page-configuration)
5.  [Development](#development)
    -   [Contributors](#contributors)
    -   [Testing](#testing)
6.  [License](#license)


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
server's command line (see [below](#batch-processing)).


### Versions

This extension is [semantically versioned](http://semver.org). In short, this
means that the first version number (the 'major') only changes on substantial
changes. The second number (the 'minor') changes when features are added or
significantly improved. The third number (the 'patch level') changes when bugs
are fixed.

Version | Date | Major changes ||
-|-|-|-
5 | 09-2017 | Rewrote the entire extension; vastly improved namespace support; some breaking changes | [Details][v5.0.0]
4 | 11-2016 | Changed format of the extension for MediaWiki version 1.25; added basic namespace support | [Details][v4.0.0]
3 | 02-2015 | Added magic words; improved performance | [Details][3.0.0]
2 | 11-2013 | Introduced smart mode | [Details][2.0.0]
1 | 05-2012 | First stable release |


[v5.0.0]: https://github.com/bovender/LinkTitles/releases/tag/v5.0.0
[v4.0.0]: https://github.com/bovender/LinkTitles/releases/tag/v4.0.0
[3.0.0]: https://github.com/bovender/LinkTitles/compare/2.4.1...3.0.0
[2.0.0]: https://github.com/bovender/LinkTitles/compare/1.8.1...2.0.0

For more details, click the 'Details' links, see the `NEWS` file in the
repository for a user-friendly changelog, or study the commit messages.


Installation
------------

To obtain the extension, you can either download a compressed archive from the
[Github releases page](https://github.com/bovender/LinkTitles/releases): Choose
one of the 'Source code' archives and extract it in your Wiki's `extension`
folder. Note that these archives contain a folder that is named after the
release version, e.g. `LinkTitles-5.0.0`. You may want to rename the folder to
`LinkTitles`.

Alternatively (and preferred by the author), if you have [Git](https://git-scm.com),
you can pull the repository in the usual way into the `extensions` folder.

To activate the extension, add the following to your `LocalSettings.php` file:

    wfLoadExtension( 'LinkTitles' );

Do not forget to adjust the [configuration](#configuration) to your needs.

If your MediaWiki version is really old (1.24 and older), you need to use
a [different mechanism](https://www.mediawiki.org/wiki/Manual:Extensions#Installing_an_extension).

Usage
-----

### Editing a page

By default, the LinkTitles extension will add links to existing pages whenever
you edit and save a page. Unless you changed the configuration variables, it will
link whole words only, prefer longer target page titles over shorter ones, skip
headings, and add multiple links if a page title appears more than once on the
page. All of this is configurable; see the [Configuration](#configuration)
section.

### Preventing automatic linking after minor edits

If the 'minor edit' check box is marked when you save a page, the extension will
not add links to the page.

### Viewing a page

If you do not want the LinkTitles extension to modify the page sources, you can
also have links added whenever a page is being viewed (or, technically, when it
is being rendered). MediaWiki caches rendered pages. Therefore, links do not need
to be added every time a page is being viewed. See the
[`$wgLinkTitlesParseOnRender`](#linking-when-a-page-is-rendered-for-display)
configuration variable.

### Including and excluding pages with Magic Words

Add the magic word **`__NOAUTOLINKS__`** to a page to prevent automatic linking
of page titles.

The presence of **`__NOAUTOLINKTARGET__`** prevents a page from being
automatically linked to from other pages.

### Enable or disable automatic linking for sections

To **exclude** a section on your page from automatic linking, wrap it in
**`<noautolinks>...</noautolinks>`** tags.

To **include** a section on your page for automatic linking, wrap it in
**`<autolinks>...</autolinks>`** tags. Of course this only makes sense if both
`$wgLinkTitlesParseOnEdit` and `$wgLinkTitlesParseOnRender` are set to `false`
*or* if the page contains the `__NOAUTOLINKS__` magic word.

### Namespace support

By default, LinkTitles will only process pages in the `NS_MAIN` namespace (i.e.,
'normal' Wiki pages). You can  modify the configuration to process pages in
other 'source' namespaces as well. By default, LinkTitles will only link to
pages that are in the same namespace as the page being edited or viewed. Again,
additional 'target' namespaces may be added in the
[configuration](#dealing-with-custom-namespaces).

If a page contains another page's title that is prefixed with the namespace
(e.g. `my_namspace:other page`), LinkTitles will _not_ add a link. It is assumed
that if someone deliberately types a namespace-qualified page title, they might
just as well add the link markup (`[[...]]`) as well. It is the LinkTitles
extension's intention to facilitate writing non-technical texts and have links
to existing pages added automatically.

### Batch processing

The extension provides two methods to batch-process all pages in a Wiki: A
special page (i.e., graphical user interface) and a command-line maintenance
script.

#### Special:LinkTitles

The special page provides a simple web interface to trigger batch processing. To
avoid blocking the web server for too long, the page will frequently reload
itself (this can be controlled by the `$wgLinkTitlesSpecialPageReloadAfter`
configuration variable that the administrator can set in the `LocalSettings.php`
file).

For security reasons, by default only users in the 'sysop' group are allowed to
view the special page (otherwise unauthorized people could trigger a parsing of
your entire wiki). To allow other user groups to view the page as well, add a
line

    $wgGroupPermissions ['<groupname>']['linktitles-batch'] = true;

to `LocalSettings.php`.

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

    php linktitles-cli.php -s 37

See all available options with:

    php linktitles-cli.php -h


Configuration
-------------

To change the configuration, set the variables in your `LocalSettings.php` file.
The code lines below show the default values of the configuration variables.

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
the links in the Wiki markup.

Note that MediaWiki caches rendered pages in the database, so that pages rarely
need to be rendered. Rendering is whenever a page is viewed and saved.
Therefore, whether you want to enable both parse-on-edit and parse-on-render
depends on whether you want to have links (`[[...]]`) added to the Wiki markup.

Please note that the extension will work on a fully built page when this mode is
enabled; therefore, it *will* add links to text transcluded from templates,
regardless of the configuration setting of `$wgLinkTitlesSkipTemplates`.

You can purge the page cache and trigger rendering by adding `?action=purge` to
the URL.

### Enabling case-insensitive linking (smart mode)

    $wgLinkTitlesSmartMode = true;

With smart mode enabled, the extension will first perform a case-sensitive
search for page titles in the current page; then it will search for occurrences
of the page titles in a case-insensitive way and add aliased ('piped') links.
Thus, if you have a page `MediaWiki Extensions`, but write `Mediawiki
extensions` (with a small 'e') in your text, LinkTitles would generate a link
`[[MediaWiki Extensions|Mediawiki extensions]]`, obviating the need to add
dummy pages for variants of page titles with different cases.

Smart mode is enabled by default. You can disable it to increase performance of
the extension.


### Dealing with custom namespaces

    $wgLinkTitlesSourceNamespace = [];

Specifies additional namespaces for pages that should be processed by the
LinkTitles extension. If this is an empty array (or anything else that PHP
evaluates to `false`), the default namespace `NS_MAIN` will be assumed.

The values in this array must be numbers/namespace constants (`NS_xxx`).

    $wgLinkTitlesTargetNamespaces = [];

By default, only pages in the same namespace as the page being edited or viewed
will be considered as link targets. If you want to link to pages in other
namespaces, list them here. Note that the source page's own namespace will also
be included, unless you change the `$wgLinkTitlesSamenamespace` option.

The values in this array must be numbers/namespace constants (`NS_xxx`).

    $wgLinkTitlesSamenamespace = true;

If you do not want to have a page's own namespace included in the possible
target namespaces, set this to false. Of course, if `$wgLinkTitlesSameNamespace`
is `false` and `$wgLinkTitlesTargetNamespaces` is empty, LinkTitle will add
no links at all because there are no target namespaces at all.

#### Example: Default configuration

    $wgLinkTitlesSourceNamespace = [];
    $wgLinkTitlesTargetNamespaces = [];
    $wgLinkTitlesSamenamespace = true;

Process pages in the `NS_MAIN` namespace only, and add links to the `NS_MAIN`
namespace only (i.e., the same namespace that the source page is in).

#### Example: Custom namespace only

    $wgLinkTitlesSourceNamespace = [ NS_MY_NAMESPACE];
    $wgLinkTitlesTargetNamespaces = [];
    $wgLinkTitlesSamenamespace = true;

Process pages in the `NS_MY_NAMESPACE` namespace only, and add links to the
`NS_MY_NAMESPACE` namespace only (i.e., the same namespace that the source page
is in).

#### Example: Link to `NS_MAIN` only

    $wgLinkTitlesSourceNamespace = [ NS_MY_NAMESPACE];
    $wgLinkTitlesTargetNamespaces = [ NS_MAIN ];
    $wgLinkTitlesSamenamespace = false;

Process pages in the `NS_MY_NAMESPACE` namespace only, and add links to the
`NS_MAIN` namespace only. Do not link to pages that are in the same namespace
as the source namespace (i.e., `NS_MY_NAMESPACE`).

### Linking or skipping headings

    $wgLinkTitlesParseHeadings = false;

Determines whether or not to add links to headings. By default, the extension
will leave your (sub)headings untouched. Only applies to parse-on-edit!

There is a **known issue** that the extension regards incorrectly formatted
headings as headings. Consider this line:

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

    $wgLinkTitlesMinimumTitleLength = 4;

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

    $wgLinkTitlesBlackList = [ 'Some special page title', 'Another one' ];

Keep in mind that a MediaWiki page title always starts with a capital letter
unless you have `$wgCapitalLinks = false;` in your `LocalSettings.php`.
**Therefore, if you have lowercase first letters in the black list array, they
will have no effect.**

### Dealing with templates

    $wgLinkTitlesSkipTemplates = false;

If set to true, do not parse the variable text of templates, i.e. in `{{my
template|some variable=some content}}`, leave the entire text between the curly
brackets untouched. If set to false (default setting), the text after the pipe
symbol (`|`) will be parsed.

Note: This setting works only with parse-on-edit; it does not affect
parse-on-render! This is because the templates have already been transcluded
(expanded) when the links are added during rendering.

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

    $wgLinkTitlesSpecialPageReloadAfter = 1; // seconds

The `LinkTitles:Special` page performs batch processing of pages by repeatedly
calling itself. This happens to prevent timeouts on your server. The default
reload interval is 1 second.


Development
-----------

If you wish to contribute, please issue pull requests against the `develop`
branch, as I follow Vincent Driessen's advice on [A successful Git branching
model](http://nvie.com/git-model) (knowing that there are [alternative
workflows](http://scottchacon.com/2011/08/31/github-flow.html)).

The `master` branch contains stable releases only, so it is safe to pull the
master branch if you want to install the extension for your own Wiki.


### Contributors

-   Daniel Kraus (@bovender), main developer
-   Ulrich Strauss (@c0nnex), initial support for namespaces
-   Brent Laabs (@labster), code review and bug fixes
-   @tetsuya-zama, bug fix
-   @yoshida3669, namespace-related bug fixes
-   Caleb Mingle (@dentafrice), bug fix


### Testing

Starting from version 5, LinkTitles finally comes with phpunit tests. The code
is not 100% covered yet. If you find something does not work as expected, let me
know and I will try to add unit tests and fix it.

Here's how I set up the testing environment. This may not be the canonical way
to do it. Basic information on testing MediaWiki can be found
[here](https://www.mediawiki.org/wiki/Manual:PHP_unit_testing).

The following assumes that you have an instance of MediaWiki running locally on
your development machine. This assumes that you are running Linux (I personally
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

        wfLoadExtensions( 'LinkTitles' );

  And ensure the settings file contains the following:

        $wgShowDBErrorBacktrace = true;

5.  Create a symbolic link to your copy of the LinkTitles repository:

        cd ~/Code/mediawiki/extensions
        ln -s ~/Code/LinkTitles

6.  Make sure your local MediaWiki instance is up to date. Otherwise phpunit may
fail and tell you about database problems.

  This is because the local database is used as a template for the unit tests.
  For example, I initially had MW 1.26 installed on my laptop, but the cloned
  repository was MW 1.29.1. It's probably also possible to clone the repository
  with a specific version tag which matches your local installation.

7.  Run the tests:

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
