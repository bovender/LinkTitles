# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

For changes prior to version 6.0.0, please see [`NEWS.old`](news.old).

## [8.1.0][] - 2022-03-13

### New

- Added a `--verbose` (`-v`) option to the commandline script.
- Added a 'shebang' line to the commandline script so that it can be invoked
  directly.

### Fixed

- Ensure compatibility with the PageForms extension (#58)

## [8.0.1][] - 2021-05-08

### Fixed

- Fixed a regression concerning `linktitles-cli` progress display that was
  introduced in v8.0.0.
- Prevent a division-by-zero error that could occur on the LinkTitles special
  page (#55).
- Fixed the message that is shown when linking that was triggered from the
  special page has been completed.

## [8.0.0][] - 2021-04-06

### Changed

- The minimum required version of MediaWiki is now 1.35.
- The values of `data-*` attributes in HTML tags are now being ignored.

### Fixed

- Replace PageContentSave with MultiContentSave to fix compatibility with MediaWiki 1.35.
- The default value for wgLinkTitlesParseOnRender is change back to `false` as support
  for MediaWiki 1.35+ is fixed.
- Progress reporting by `linktitles-cli.php` is no longer incorrect when a start
  page is defined.

## [7.0.0][] - 2020-12-23

### Changed

- The minimum required version of MediaWiki is now 1.32.

### Fixed

- Fixed compatibility with MediaWiki version 1.35.

## [6.0.0][] - 2019-12-31

### Changed

- Because automatic linking upon page save no longer works with MediaWiki
  versions 1.32 and newer, the default value of the `$wgLinkTitlesParseOnRender`
  is now `true`. Please see `README.md` for more information.

### Fixed

- Prevent crash that occurred with MediaWiki version 1.34 due to a renamed
  constant (DB_SLAVE was renamed to DB_REPLICA). NOTE that the minimum
  required version of MediaWiki is now 1.28 (which is an obsolete version).

[8.1.0]: https://github.com/bovender/LinkTitles/releases/tag/v8.1.0
[8.0.1]: https://github.com/bovender/LinkTitles/releases/tag/v8.0.1
[8.0.0]: https://github.com/bovender/LinkTitles/releases/tag/v8.0.0
[7.0.0]: https://github.com/bovender/LinkTitles/releases/tag/v7.0.0
[6.0.0]: https://github.com/bovender/LinkTitles/releases/tag/v6.0.0
