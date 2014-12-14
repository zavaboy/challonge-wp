<?php
/**
 * @package Challonge
 */
/*
Plugin Name: Challonge
Plugin URI: http://wordpress.org/plugins/challonge
Description: Integrates <a href="http://challonge.com/">Challonge</a>, a handy bracket generator, into WordPress.
Version: 1.1.4
Author: Ivik Injerd
Author URI: http://zavaboy.org/
Text Domain: Challonge
Domain Path: /languages/
License: MIT
*/

/*
The MIT License (MIT)

Copyright (c) 2013-2014 Ivik Injerd

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

/*
3rd Party Software included in this package:

File: t27duck-challonge-php/challonge.class.php
challonge-php 1.0.1
https://bitbucket.org/t27duck/challonge-php
(c) 2014 Tony Drake
License: MIT
License URL: http://www.opensource.org/licenses/mit-license.php

File: jquery.challonge.js
Challonge jQuery Plugin 0.1.1
https://github.com/challonge/challonge-jquery-plugin
(c) 2013 Challonge! LLC
Contributers: Adam Darrah, David Cornelius
License: MIT
License URL: https://github.com/jquery/jquery-color/blob/2.1.2/MIT-LICENSE.txt

*/

// TODO: Add phpdoc (maybe someday...)
// TODO: Before release, update version, changelog, readme, screenshots

// Exit on direct request.
defined( 'ABSPATH' ) OR exit;

// The Challonge plugin
require_once( 'class-challonge-plugin.php' );
$Challonge = Challonge_Plugin::getInstance();

define( 'CHALLONGE_VERSION', Challonge_Plugin::VERSION );
