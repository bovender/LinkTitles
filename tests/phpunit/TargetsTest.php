<?php
/**
 * Tests the LinkTitles\Targets class.
 *
 * @group bovender
 * @group Database
 */
class TargetsTest extends LinkTitles\TestCase {

	/**
	 * This test asserts that the list of potential link targets is 0
	 * @return [type] [description]
	 */
	public function testTargets() {
		$title = \Title::newFromText( 'link target' );
		$targets = LinkTitles\Targets::default( $title, new LinkTitles\Config() );

		// Count number of articles: Inspired by updateArticleCount.php maintenance
		// script: https://doc.wikimedia.org/mediawiki-core/master/php/updateArticleCount_8php_source.html
		$dbr = wfGetDB( DB_SLAVE );
		$counter = new SiteStatsInit( $dbr );
		$count = $counter->pages();

		$this->assertEquals( $targets->queryResult->numRows(), $count );
	}
}
