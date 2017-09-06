<?php
/**
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
 * @group bovender
 * @group Database
 */
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
    $page = WikiPage::newFromId( $pageId );
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
        false,
        'This page should *not* link to the link target',
        'This page should *not* link to the link target'
      ]
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
    $content = $page->getContent();
    $output = $content->getParserOutput( $title, null, null, false );
    $lines = explode( "\n", $output->getText() );
    $this->assertRegexp( $expectedOutput, $lines[0] );
  }

  public function provideParseOnRenderData() {
    return [
      [
        true, // parseOnRender
        'This page should link to the link target but not to test page',
        '_This page should link to the <a href=[^>]+>link target</a> but not to test page_'
      ],
      [
        false,
        'This page should not link to the link target',
        '_This page should not link to the link target_'
      ]
    ];
  }
}
