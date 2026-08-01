<?php

/**
 * Copyright 2012-2024 Daniel Kraus <bovender@bovender.de> ('bovender')
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
namespace LinkTitles;
use MediaWikiIntegrationTestCase;

abstract class TestCase extends MediaWikiIntegrationTestCase  {
	protected function setUp(): void
	{
		// Guard against a stale singleton left behind by a previous test
		// (e.g. one that queried a fabricated namespace for testing
		// purposes) before parent::setUp() runs any fixture inserts that
		// would otherwise trigger the LinkTitles hooks against it.
		Targets::invalidate();
		parent::setUp();
	}

	protected function tearDown(): void
	{
		parent::tearDown();
	}

	public function addDBDataOnce() {
		parent::addDBDataOnce();
		$this->insertPage( 'link target', 'This page serves as a link target' );
		Targets::invalidate(); // force re-querying the pages table
	}

	protected function getPageText( \WikiPage $page ) {
		$content = $page->getContent();
		return $page->getContentHandler()->serializeContent( $content );
	}
}
