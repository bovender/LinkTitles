@mainpage LinkTitles
@author    [Daniel Kraus (bovender)](http://www.mediawiki.org/wiki/User:Bovender)
@date      2012-2017
@copyright [GNU GPL v2+](http://www.gnu.org/licenses/gpl-2.0.html)

%LinkTitles source code documentation
=====================================

This is the [source code][] documentation for the [LinkTitles][] extension
for [MediaWiki][].

With version 5.0, the code is more extensively commented than ever. Version 5
brought a major refactoring, and the extension now consists of several classes
with clearly defined concerns. Look at the class comments to find out what they
do.

@note The source code that is referenced in this documentation may not
necessarily reflect the latest code in the repository! Make sure to check
out the Git repository for the latest code.


A note on the publication of the source code documentation
----------------------------------------------------------

The documentation is automatically generated from the source code by
[Doxygen][]. A special branch of the Git repository, [`gh-pages`][gh-pages],
was created that holds the [GitHub project pages][github-pages]. To make use
of the project page facility provided by GitHub, the root directory of a
repository needs to be [erased][gh-erase] first before content is added.
Since this would remove the source code from which the documentation is
generated, deleting all files in the root directory while on the `gh-pages`
branch was not feasible.

The solution was to pull down the `gh-pages` branch as a submodule into an
empty `gh-pages\` subdirectory:

~~~~{.sh}
git submodule add -b gh-pages git@github.com:bovender/LinkTitles.git gh-pages
~~~~

This command tells git-submodule to only pull down the `gh-pages` branch as
a submodule -- we don't need to pull down the master branch into the same
repository again.


[source code]:  http://github.com/bovender/LinkTitles
[LinkTitles]:   http://www.mediawiki.org/wiki/Extension:LinkTitles
[MediaWiki]:    http://www.mediawiki.org
[Doxygen]:      http://www.doxygen.org
[github-pages]: https://pages.github.com/
[gh-pages]:     https://github.com/bovender/LinkTitles/tree/gh-pages
[gh-erase]:     https://help.github.com/articles/creating-project-pages-manually#create-a-gh-pages-branch
