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
 * Tests the LinKTitles\Splitter class.
 *
 * @group bovender
 */
class SplitterTest extends MediaWikiTestCase {
	/**
	 * @dataProvider provideSplitData
	 */
	public function testSplit( $skipTemplates, $parseHeadings, $input, $expectedOutput ) {
		$config = new LinkTitles\Config();
		$config->skipTemplates = $skipTemplates;
		$config->parseHeadings = $parseHeadings;
		LinkTitles\Splitter::invalidate();
		$splitter = LinkTitles\Splitter::singleton( $config );
		$this->assertSame( $skipTemplates, $splitter->config->skipTemplates, 'Splitter has incorrect skipTemplates config');
		$this->assertSame( $parseHeadings, $splitter->config->parseHeadings, 'Splitter has incorrect parseHeadings config');
		$this->assertSame( $expectedOutput, $splitter->split( $input ) );
	}

	// TODO: Add more examples.
	public static function provideSplitData() {
		return [
			[
				true, // skipTemplates
				false, // parseHeadings
				'this may be linked [[this may not be linked]]',
				[ 'this may be linked ', '[[this may not be linked]]', '' ]
			],
			[
				true, // skipTemplates
				false, // parseHeadings
				'this may be linked <gallery>this may not be linked</gallery>',
				[ 'this may be linked ', '<gallery>this may not be linked</gallery>', '' ]
			],
			[
				true, // skipTemplates
				false, // parseHeadings
				'With skipTemplates = true, this may be linked {{mytemplate|param=link target}}',
				[ 'With skipTemplates = true, this may be linked ', '{{mytemplate|param=link target}}', '' ]
			],
			[
				false, // skipTemplates
				false, // parseHeadings
				'With skipTemplates = false, this may be linked {{mytemplate|param=link target}}',
				[ 'With skipTemplates = false, this may be linked ', '{{mytemplate|param=', 'link target}}' ]
			],
			[
				true, // skipTemplates
				false, // parseHeadings
				'With skipTemplates = true, this may be linked {{mytemplate|param={{transcluded}}}}',
				[ 'With skipTemplates = true, this may be linked ', '{{mytemplate|param={{transcluded}}}}', '' ]
			],
			[
				true, // skipTemplates
				true, // parseHeadings
				"With parseHeadings = true,\n==a heading may be linked==\n",
				[ "With parseHeadings = true,\n==a heading may be linked==\n" ]
			],
			[
				true, // skipTemplates
				false, // parseHeadings
				// no trailing newline in the following string because it would be swallowed
				"With parseHeadings = false,\n==a heading may not be linked==",
				[ "With parseHeadings = false,\n", "==a heading may not be linked==", '' ]
			],
			// Improperly formatted headings cannot be dealt with appropriately for now
			// [
			// 	true, // skipTemplates
			// 	false, // parseHeadings
			// 	"With parseHeadings = false,\n==an improperly formatted heading may be linked=\n",
			// 	[ "With parseHeadings = false,\n==an improperly formatted heading may be linked=\n" ]
			// ],
		];
	}
}
