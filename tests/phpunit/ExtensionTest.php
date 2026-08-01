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

/**
 * @group bovender
 * @group Database
 */

use MediaWiki\Title\Title;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\MediaWikiServices;

class ExtensionTest extends LinkTitles\TestCase {

	/**
	 * @dataProvider provideParseOnEditData
	 */
	public function testParseOnEdit( $parseOnEdit, $input, $expectedOutput) {
		$this->setMwGlobals( [
			'wgLinkTitlesParseOnEdit' => $parseOnEdit,
			'wgLinkTitlesParseOnRender' => !$parseOnEdit
		] );
		$pageId = $this->insertPage( 'test page', $input )['id'];
		$title = Title::newFromID( $pageId );
		$wpf = MediaWikiServices::getInstance()->getWikiPageFactory();
		$page = $wpf->newFromTitle( $title );
		$this->assertSame( $expectedOutput, self::getPageText( $page ) );
	}

	public function provideParseOnEditData() {
		return [
			[
				true, // parseOnEdit
				'This page should link to the link target but not to test page',
				'This page should link to the [[link target]] but not to test page'
			],
			[
				false, // parseOnEdit
				'This page should *not* link to the link target',
				'This page should *not* link to the link target'
			],
			[
				true, // parseOnEdit
				'With __NOAUTOLINKS__, this page should not link to the link target',
				'With __NOAUTOLINKS__, this page should not link to the link target'
			],
		];
	}


	/**
	 * @dataProvider provideParseOnRenderData
	 */
	public function testParseOnRender( $parseOnRender, $input, $expectedOutput) {
		$this->setMwGlobals( [
			'wgLinkTitlesParseOnEdit' => false, // do not modify the page as we create it
			'wgLinkTitlesParseOnRender' => $parseOnRender
		] );
		$title = $this->insertPage( 'test page', $input )['title'];
		$page = new WikiPage( $title );
		$userFactory = MediaWikiServices::getInstance()->getUserFactory();
		$user = $userFactory->newAnonymous();
		$output = $page->getParserOutput( new ParserOptions( $user ), null, true );
		$lines = explode( "\n", $output->getRawText() );
		$this->assertMatchesRegularExpression( $expectedOutput, $lines[0] );
	}

	public function provideParseOnRenderData() {
		return [
			[
				true, // parseOnRender
				'This page should link to the link target but not to the test page',
				'_This page should link to the <a href=[^>]+>link target</a> but not to the test page_'
			],
			[
				false, // parseOnRender
				'This page should not link to the link target',
				'_This page should not link to the link target_'
			],
			[
				true, // parseOnRender
				'__NOAUTOLINKS__With noautolinks magic word, this page should not link to the link target',
				'_With noautolinks magic word, this page should not link to the link target_'
			],
			[
				true, // parseOnRender
				'__NOAUTOLINKS__With noautolinks magic word, <autolinks>link target in autolinks tag</autolinks> should be linked',
				'_With noautolinks magic word, <a href=[^>]+>link target</a> in autolinks tag should be linked_'
			],
			[
				true, // parseOnRender
				'<noautolinks>In a noautolinks tag, link target should NOT be linked</noautolinks>',
				'_In a noautolinks tag, link target should NOT be linked_'
			],
			[
				true, // parseOnRender
				'<noautolinks>In a noautolinks tag, <autolinks>link target in autolinks tag</autolinks> should be linked</noautolinks>',
				'_In a noautolinks tag, <a href=[^>]+>link target</a> in autolinks tag should be linked_'
			],
		];
	}
}
