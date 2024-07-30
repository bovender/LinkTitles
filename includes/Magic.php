<?php
/*
 *      Copyright 2012-2024 Daniel Kraus <bovender@bovender.de> ('bovender')
 *      
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *      
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *      
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 */
/*! @file LinkTitles_Magic.php
 */
 
/// Holds the two magic words that the extension provides.
$magicWords = array();

/// Default magic words in English.
$magicWords['en'] = array(
	'MAG_LINKTITLES_NOAUTOLINKS' => array(0, '__NOAUTOLINKS__'),
	'MAG_LINKTITLES_NOTARGET' => array(0, '__NOAUTOLINKTARGET__')
);
