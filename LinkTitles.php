<?php
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'LinkTitles' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['LinkTitles'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['LinkTitlesMagic'] = __DIR__ . '/includes/LinkTitles_Magic.php';
	wfWarn(
		'Deprecated PHP entry point used for LinkTitles extension. ' .
		'Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the LinkTitles extension requires MediaWiki 1.25+' );
}
