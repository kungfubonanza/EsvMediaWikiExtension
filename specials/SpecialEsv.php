<?php
/**
 * EsvLicense SpecialPage for BoilerPlate extension
 *
 * @file
 * @ingroup Extensions
 */

class SpecialEsv extends SpecialPage {
	public function __construct() {
		parent::__construct( 'EsvLicense' );
	}

	/**
	 * Show the page to the user
	 *
	 * @param string $sub The subpage string argument (if any).
	 *  [[Special:EsvLicense/subpage]].
	 */
	public function execute( $sub ) {
		$out = $this->getOutput();
		//$out->setPageTitle( $this->msg( 'esv-license' ) );
		$out->setPageTitle('ESV License');
		$out->addWikiMsg( 'esv-license' );
	}

	protected function getGroupName() {
		return 'other';
	}
}
