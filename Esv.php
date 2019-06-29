<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'Esv' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['Esv'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['EsvAlias'] = __DIR__ . '/Esv.i18n.alias.php';
	wfWarn(
		'Deprecated PHP entry point used for Esv extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the Esv extension requires MediaWiki 1.25+' );
}
