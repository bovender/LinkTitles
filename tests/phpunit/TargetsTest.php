<?php
/**
 * Copyright 2012-2018 Daniel Kraus <bovender@bovender.de> ('bovender')
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 * @author Daniel Kraus <bovender@bovender.de>
 */

/**
 * Tests the LinkTitles\Targets class.
 *
 * @group bovender
 * @group Database
 */
class TargetsTest extends LinkTitles\TestCase {

	public function testTargets() {
		$config = new LinkTitles\Config();
		// Include the custom namespace with index 4000 in the count. This is a
		// very ugly hack. If the custom namespace index in
		// LinkTitlesLinkerTest::testLinkContentTargetNamespaces() is every changed,
		// this test will fail.
		$config->targetNamespaces = [ 4000 ];
		$title = \Title::newFromText( 'link target' );
		$targets = LinkTitles\Targets::singleton( $title, $config );

		// Count number of articles: Inspired by updateArticleCount.php maintenance
		// script: https://doc.wikimedia.org/mediawiki-core/master/php/updateArticleCount_8php_source.html
		$dbr = wfGetDB( DB_SLAVE );
		$counter = new SiteStatsInit( $dbr );
		$count = $counter->pages();

		$this->assertEquals( $targets->queryResult->numRows(), $count );
	}
}
