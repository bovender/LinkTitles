<?php
/**
 * Unit tests for the Linker class, i.e. the core functionality
 *
 * Copyright 2012-2017 Daniel Kraus <bovender@bovender.de> ('bovender')
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
 * Unit tests for the LinkTitles\Linker class.
 *
 * The test class is prefixed with 'LinkTitles' to avoid a naming collision
 * with a class that exists in the MediaWiki core.
 *
 * (Ideally the test classes should be namespaced, but when you do that, they
 * will no longer be automatically discovered.)
 *
 * @group bovender
 * @group Database
 */
class LinkTitlesLinkerTest extends LinkTitles\TestCase {
	protected $title;

	protected function setUp() {
		parent::setUp(); // call last to have the Targets object invalidated after inserting the page
	}

  public function addDBData() {
		$this->title = $this->insertPage( 'source page', 'This page is the test page' )['title'];
    $this->insertPage( 'link target', 'This page serves as a link target' );
		parent::addDBDataOnce(); // call parent after adding page to have targets invalidated
  }

	/**
	 * @dataProvider provideLinkContentTemplatesData
	 */
	public function testLinkContentTemplates( $skipTemplates, $input, $expectedOutput ) {
		$config = new LinkTitles\Config();
		$config->firstOnly = false;
		$config->skipTemplates = $skipTemplates;
		LinkTitles\Splitter::invalidate();
		$source = LinkTitles\Source::createFromTitleAndText( $this->title, $input, $config );
		$linker = new LinkTitles\Linker( $config );
		$result = $linker->linkContent( $source );
		if ( !$result ) { $result = $input; }
		$this->assertSame( $expectedOutput, $result );
	}

	public function provideLinkContentTemplatesData() {
		return [
			[
				true, // skipTemplates
				'With skipTemplates = true, a {{template|with=link target}} in it should not be linked',
				'With skipTemplates = true, a {{template|with=link target}} in it should not be linked',
			],
			[
				false, // skipTemplates
				'With skipTemplates = false, a {{template|with=link target}} in it should be linked',
				'With skipTemplates = false, a {{template|with=[[link target]]}} in it should be linked',
			],
			[
				false, // skipTemplates
				'With skipTemplates = false, a {{template|with=already linked [[link target]]}} in it should not be linked again',
				'With skipTemplates = false, a {{template|with=already linked [[link target]]}} in it should not be linked again',
			]
		];
	}

	/**
	 * @dataProvider provideLinkContentSmartModeData
	 */
	public function testLinkContentSmartMode( $capitalLinks, $smartMode, $input, $expectedOutput ) {
		$this->setMwGlobals( 'wgCapitalLinks', $capitalLinks );
		$config = new LinkTitles\Config();
		$config->firstOnly = false;
		$config->smartMode = $smartMode;
		$linker = new LinkTitles\Linker( $config );
		$source = LinkTitles\Source::createFromTitleAndText( $this->title, $input, $config );
		$result = $linker->linkContent( $source );
		if ( !$result ) { $result = $input; }
		$this->assertSame( $expectedOutput, $result );
	}

	public function provideLinkContentSmartModeData() {
		return [
			[
				true, // wgCapitalLinks
				true, // smartMode
				'With smart mode on and $wgCapitalLinks = true, this page should link to link target',
				'With smart mode on and $wgCapitalLinks = true, this page should link to [[link target]]'
			],
			[
				true, // wgCapitalLinks
				false, // smartMode
				'With smart mode off and $wgCapitalLinks = true, this page should link to link target',
				'With smart mode off and $wgCapitalLinks = true, this page should link to [[link target]]'
			],
			[
				true, // wgCapitalLinks
				true, // smartMode
				'With smart mode on and $wgCapitalLinks = true, this page should link to Link target',
				'With smart mode on and $wgCapitalLinks = true, this page should link to [[Link target]]'
			],
			[
				true, // wgCapitalLinks
				false, // smartMode
				'With smart mode off and $wgCapitalLinks = true, this page should not link to Link Target',
				'With smart mode off and $wgCapitalLinks = true, this page should not link to Link Target'
			],
			[
				false, // wgCapitalLinks
				true, // smartMode
				'With smart mode on and $wgCapitalLinks = false, this page should link to Link target',
				'With smart mode on and $wgCapitalLinks = false, this page should link to [[Link target]]'
			],
			[
				false, // wgCapitalLinks
				true, // smartMode
				'With smart mode on and $wgCapitalLinks = false, this page should link to link target',
				'With smart mode on and $wgCapitalLinks = false, this page should link to [[Link target|link target]]'
			],
			[
				false, // wgCapitalLinks
				false, // smartMode
				'With smart mode off and $wgCapitalLinks = false, this page should not link to link target',
				'With smart mode off and $wgCapitalLinks = false, this page should not link to link target'
			],
			[
				false, // wgCapitalLinks
				false, // smartMode
				'With smart mode off and $wgCapitalLinks = false, this page should not link to Link target',
				'With smart mode off and $wgCapitalLinks = false, this page should not link to [[Link target]]'
			],
			[
				false, // wgCapitalLinks
				true, // smartMode
				'With smart mode on and $wgCapitalLinks = false, this page should link to Link target',
				'With smart mode on and $wgCapitalLinks = false, this page should link to [[Link target]]'
			],
			[
				false, // wgCapitalLinks
				false, // smartMode
				'With smart mode off and $wgCapitalLinks = false, this page should not link to Link Target',
				'With smart mode off and $wgCapitalLinks = false, this page should not link to Link Target'
			],
		];
	}

	/**
	 * @dataProvider provideLinkContentFirstOnlyData
	 */
	public function testLinkContentFirstOnly( $firstOnly, $input, $expectedOutput ) {
		$config = new LinkTitles\Config();
		$config->firstOnly = $firstOnly;
		$linker = new LinkTitles\Linker( $config );
		$source = LinkTitles\Source::createFromTitleAndText( $this->title, $input, $config );
		$result = $linker->linkContent( $source );
		if ( !$result ) { $result = $input; }
		$this->assertSame( $expectedOutput, $result );
	}

	public function provideLinkContentFirstOnlyData() {
		return [
			[
				false, // firstOnly
				'With firstOnly = false, link target is a link target multiple times',
				'With firstOnly = false, [[link target]] is a [[link target]] multiple times'
			],
			[
				false, // firstOnly
				'With firstOnly = false, [[link target]] is a link target multiple times',
				'With firstOnly = false, [[link target]] is a [[link target]] multiple times'
			],
			[
				true, // firstOnly
				'With firstOnly = true, link target is a link target only once',
				'With firstOnly = true, [[link target]] is a link target only once'
			],
			[
				true, // firstOnly
				'With firstOnly = true, [[link target]] is a link target only once',
				'With firstOnly = true, [[link target]] is a link target only once'
			],
		];
	}

	/**
	 * @dataProvider provideLinkContentHeadingsData
	 */
	public function testLinkContentHeadings( $parseHeadings, $input, $expectedOutput ) {
		$config = new LinkTitles\Config();
		$config->parseHeadings = $parseHeadings;
		LinkTitles\Splitter::invalidate();
		$source = LinkTitles\Source::createFromTitleAndText( $this->title, $input, $config );
		$linker = new LinkTitles\Linker( $config );
		$result = $linker->linkContent( $source );
		if ( !$result ) { $result = $input; }
		$this->assertSame( $expectedOutput, $result );
	}

	public function provideLinkContentHeadingsData() {
		return [
			[
				true, // parseHeadings
				"With parseHeadings = true,\n== a heading with link target in it ==\n should be linked",
				"With parseHeadings = true,\n== a heading with [[link target]] in it ==\n should be linked",
			],
			[
				false, // parseHeadings
				"With parseHeadings = false,\n== a heading with link target in it ==\n should not be linked",
				"With parseHeadings = false,\n== a heading with link target in it ==\n should not be linked",
			],
		];
	}

	public function testLinkContentBlackList() {
		$config = new LinkTitles\Config();
		$config->blackList = [ 'Foo', 'Link target', 'Bar' ];
		LinkTitles\Targets::invalidate();
		$linker = new LinkTitles\Linker( $config );
		$text = 'If the link target is blacklisted, it should not be linked';
		$source = LinkTitles\Source::createFromTitleAndText( $this->title, $text, $config );
		$result = $linker->linkContent( $source );
		if ( !$result ) { $result = $text; }
		$this->assertSame( $text, $result );
	}

	// Tests for namespace handling are commented out until I find a way to add
	// a custom namespace during testing. (The assertTrue assertion below fails.)

	/**
	 * @dataProvider provideLinkContentNamespacesData
	 */
	public function testLinkContentTargetNamespaces( $namespaces, $input, $expectedOutput ) {
		$config = new LinkTitles\Config();
		$config->targetNamespaces = $namespaces;

	 	$ns = 4000;
		$nsText = 'customnamespace';
		$this->mergeMwGlobalArrayValue( 'wgExtraNamespaces', [ $ns => $nsText ] );

		// Reset namespace caches.
		// See https://stackoverflow.com/q/45974979/270712
		MWNamespace::getCanonicalNamespaces( true );
		global $wgContLang;
		$wgContLang->resetNamespaces();
		$this->assertTrue( MWNamespace::exists( $ns ), "The namespace with id $ns should exist!" );

		$this->insertPage( "in custom namespace", 'This is a page in a custom namespace', $ns );
		LinKTitles\Targets::invalidate();
		$linker = new LinkTitles\Linker( $config );
		$source = LinkTitles\Source::createFromTitleAndText( $this->title, $input, $config );
		$result = $linker->linkContent( $source );
		if ( !$result ) { $result = $input; }
		$this->assertSame( $expectedOutput, $result );
	}

	public function provideLinkContentNamespacesData() {
		return [
	 		[
	 			[], // namespaces
	 			'With targetNamespaces = [], page in custom namespace should not be linked',
	 			'With targetNamespaces = [], page in custom namespace should not be linked'
	 		],
	 		[
	 			[ 4000 ], // namespaces
	 			'With targetNamespaces = [ 4000 ], page in custom namespace should be linked',
	 			'With targetNamespaces = [ 4000 ], page [[customnamespace:In custom namespace|in custom namespace]] should be linked'
	 		],
		];
	}
}
