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
 */
class TargetTest extends MediaWikiTestCase {

	/**
	 * @dataProvider provideStartOnly
	 */
	public function testTargetWordStartOnly( $enabled, $delimiter ) {
		$config = new LinkTitles\Config();
		$config->wordStartOnly = $enabled;
		$target = new LinKTitles\Target( NS_MAIN, 'test page', $config );
		$this->assertSame( $delimiter, $target->wordStart );
	}

	public static function provideStartOnly() {
		return [
			[ true, '(?<!\pL)' ],
			[ false, '' ]
		];
	}

	/**
	 * @dataProvider provideEndOnly
	 */
	public function testTargetWordEndOnly( $enabled, $delimiter ) {
		$config = new LinkTitles\Config();
		$config->wordEndOnly = $enabled;
		$target = new LinKTitles\Target( NS_MAIN, 'test page', $config );
		$this->assertSame( $delimiter, $target->wordEnd );
	}

	public static function provideEndOnly() {
		return [
			[ true, '(?!\pL)' ],
			[ false, '' ]
		];
	}
}
