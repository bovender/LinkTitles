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

  public function testParseOnEdit() {
    $this->setMwGlobals( [
      'wgLinkTitlesParseOnEdit' => true,
      'wgLinkTitlesParseOnRender' => false
    ] );
    $pageId = $this->insertPage( 'test page', 'This page should link to the link target but not to test page' )['id'];
    $page = WikiPage::newFromId( $pageId );
    $this->assertSame( 'This page should link to the [[link target]] but not to test page', self::getPageText( $page ) );
  }

  public function testDoNotParseOnEdit() {
    $this->setMwGlobals( [
      'wgLinkTitlesParseOnEdit' => false,
      'wgLinkTitlesParseOnRender' => false
    ] );
    $pageId = $this->insertPage( 'test page', 'This page should not link to the link target' )['id'];
    $page = WikiPage::newFromId( $pageId );
    $this->assertSame( 'This page should not link to the link target', self::getPageText( $page ) );
  }
}
