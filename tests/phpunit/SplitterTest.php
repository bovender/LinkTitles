<?php
/**
 * @group bovender
 */
class SplitterTest extends MediaWikiTestCase {
	/**
	 * @dataProvider provideSplitData
	 */
	public function testSplit( $input, $expectedOutput ) {
		$splitter = LinkTitles\Splitter::default();
		$this->assertSame( $expectedOutput, $splitter->split( $input ) );
	}

	// TODO: Add more examples.
	public static function provideSplitData() {
		return [
			[
				'this may be linked [[this may not be linked]]',
				[ 'this may be linked ', '[[this may not be linked]]', '' ]
			],
			[
				'this may be linked <gallery>this may not be linked</gallery>',
				[ 'this may be linked ', '<gallery>this may not be linked</gallery>', '' ]
			],
			[
				'this may be linked {{mytemplate|param={{transcluded}}}}',
				[ 'this may be linked ', '{{mytemplate|param={{transcluded}}}}', '' ]
			],
		];
	}
}
