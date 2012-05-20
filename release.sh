#!/bin/sh

# This script packs the relevant files of the LinkTitles
# extension into two archive files that contain the current
# git tag as the version number.

tar cvzf release/LinkTitles-`git describe --tags`.tar.gz gpl-*.txt HISTORY LinkTitles.* --exclude '*~' --transform 's,^,LinkTitles/,'
