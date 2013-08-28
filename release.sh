#!/bin/bash

# This script packs the relevant files of the LinkTitles
# extension into two archive files that contain the current
# git tag as the version number.

if [[ -z $1 ]]; then
	echo "Usage: $(basename $0) VERSION."
	echo "Missing VERSION!"
	exit 1
fi

FILENAME="release/LinkTitles-$1.tar.gz"

# Pack the relevant files into at tarball, renaming the paths to include the
# root path "LinktTitles".
tar cvzf $FILENAME gpl-*.txt NEWS LinkTitles.* --exclude '*~' --transform 's,^,LinkTitles/,'

# Add the tarball to the repository, commit it, then tag the commit and push to origin.
git add $FILENAME
git commit -m "Release version $1"
git tag -a $1 -m "Version $1."
git push
git push --tags
