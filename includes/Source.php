<?php
/**
 * The LinkTitles\Source represents a Wiki page to which links may be added.
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
namespace LinkTitles;

/**
 * Represents a page that is a potential link target.
 */
class Source {
	/**
	 * The LinKTitles configuration for this Source.
	 *
	 * @var Config $config
	 */
	public $config;

	private $title;

	private $text;

	private $page;

	private $content;

	/**
	 * Creates a Source object from a \Title.
	 * @param  \Title  $title  Title object from which to create the Source.
	 * @return Source          Source object created from the title.
	 */
	public static function createFromTitle( \Title $title, Config $config ) {
		$source = new Source( $config );
		$source->title = $title;
		return $source;
	}

	/**
	 * Creates a Source object with a given Title and a text.
	 *
	 * This factory can be called e.g. from a onPageContentSave event handler
	 * which knows both these parameters.
	 *
	 * @param  \Title $title Title of the source page
	 * @param  String $text  String representation of the page content
	 * @param  Config    $config   LinkTitles configuration
	 * @return Source        Source object created from the title and the text
	 */
	public static function createFromTitleAndText( \Title $title, $text, Config $config ) {
		$source = Source::createFromTitle( $title, $config);
		$source->text = $text;
		return $source;
	}

	/**
	 * Creates a Source object with a given WikiPage and a Content.
	 *
	 * This factory can be called e.g. from an onPageContentSave event handler
	 * which knows both these parameters.
	 *
	 * @param  \WikiPage $page     WikiPage to link from
	 * @param  \Content  $content  Page content
	 * @param  Config    $config   LinkTitles configuration
	 * @return Source              Source object created from the title and the text
	 */
	public static function createFromPageandContent( \WikiPage $page, \Content $content, Config $config ) {
		$source = new Source( $config );
		$source->page = $page;
		$source->content = $content;
		return $source;
	}

	/**
	 * Creates a Source object with a given Parser.
	 *
	 * @param  \Parser $parser Parser object from which to create the Source.
	 * @param  Config  $config LinKTitles Configuration
	 * @return Source          Source object created from the parser and the text.
	 */
	public static function createFromParser( \Parser $parser, Config $config ) {
		$source = new Source( $config );
		$source->title = $parser->getTitle();
		return $source;
	}

	/**
	 * Creates a Source object with a given Parser and text.
	 *
	 * This factory can be called e.g. from an onInternalParseBeforeLinks event
	 * handler which knows these parameters.
	 *
	 * @param  \Parser $parser Parser object from which to create the Source.
	 * @param  String  $text   String representation of the page content.
	 * @param  Config  $config LinKTitles Configuration
	 * @return Source          Source object created from the parser and the text.
	 */
	public static function createFromParserAndText( \Parser $parser, $text, Config $config ) {
		$source = Source::createFromParser( $parser, $config );
		$source->text = $text;
		return $source;
	}

	/**
	 * Private constructor. Use one of the factories to created a Source object.
	 * @param  Config    $config   LinkTitles configuration
	 */
	private function __construct( Config $config) {
		$this->config = $config;
	}

	/**
	 * Determines whether or not this page may be linked to.
	 * @return [type] [description]
	 */
	public function canBeLinked() {
		return $this->hasDesiredNamespace() && !$this->hasNoAutolinksMagicWord();
	}

	/**
	 * Determines whether the Source is in a desired namespace, i.e. a namespace
	 * that is listed in the sourceNamespaces config setting or is NS_MAIN.
	 * @return boolean True if the Source is in a 'good' namespace.
	 */
	public function hasDesiredNamespace() {
		return in_array( $this->getTitle()->getNamespace(), $this->config->sourceNamespaces );
	}

	/**
	 * Determines whether the source page contains the __NOAUTOLINKS__ magic word.
	 *
	 * @return boolean True if the page contains the __NOAUTOLINKS__ magic word.
	 */
	public function hasNoAutolinksMagicWord() {
		return \MagicWord::get( 'MAG_LINKTITLES_NOAUTOLINKS' )->match( $this->getText() );
	}

	/**
	 * Gets the title.
	 *
	 * @return \Title Title of the source page.
	 */
	public function getTitle() {
		if ( $this->title === null ) {
			// Access the property directly to avoid an infinite loop.
			if ( $this->page != null) {
				$this->title = $this->page->getTitle();
			} else {
				throw new Exception( 'Unable to create Title for this Source because Page is null.' );
			}
		}
		return $this->title;
	}

	/**
	 * Gets the namespace of the source Title.
	 * @return integer namespace index.
	 */
	public function getNamespace() {
		return $this->getTitle()->getNamespace();
	}

	/**
	 * Gets the Content object for the source page.
	 *
	 * The value is cached.
	 *
	 * @return \Content Content object.
	 */
	public function getContent() {
		if ( $this->content === null ) {
			$this->content = $this->getPage()->getContent();
		}
		return $this->content;
	}

	/**
	 * Determines whether the source page has content.
	 *
	 * @return boolean True if the source page has content.
	 */
	public function hasContent() {
		return $this->getContent() != null;
	}

	/**
	 * Gets the text of the corresponding Wiki page.
	 *
	 * The value is cached.
	 *
	 * @return String Text of the Wiki page.
	 */
	public function getText() {
		if ( $this->text === null ) {
			$content = $this->getContent();
			$this->text = $content->getContentHandler()->serializeContent( $content );
		}
		return $this->text;
	}

	/**
	 * Unserializes text to the page's content.
	 *
	 * @param  String   $text Text to unserialize.
	 * @return \Content       The source's updated content object.
	 */
	public function setText( $text ) {
		$this->content = $this->content->getContentHandler()->unserializeContent( $text );
		$this->text = $text;
		return $this->content;
	}

	/**
	 * Returns the source page object.
	 * @return \WikiPage WikiPage for the source title.
	 */
	public function getPage() {
		if ( $this->page === null ) {
			// Access the property directly to avoid an infinite loop.
			if ( $this->title != null) {
				$this->page = \WikiPage::factory( $this->title );
			} else {
				throw new Exception( 'Unable to create Page for this Source because Title is null.' );
			}
		}
		return $this->page;
	}
}
