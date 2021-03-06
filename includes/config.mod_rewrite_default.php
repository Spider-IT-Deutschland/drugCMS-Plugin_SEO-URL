<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Plugin Advanced Mod Rewrite default settings. This file will be included if
 * mod rewrite settings of an client couldn't loaded.
 *
 * Containing settings are taken over from contenido-4.6.15mr setup installer
 * template beeing made originally by stese.
 *
 * NOTE:
 * Changes in these Advanced Mod Rewrite settings will affect all clients, as long
 * as they don't have their own configuration.
 * PHP needs write permissions to the folder, where this file resides. Mod Rewrite
 * configuration files will be created in this folder.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend plugins
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since Contenido release 4.8.15
 *
 * {@internal
 *   created  2008-05-xx
 *
 *   $Id$:
 * }}
 *
 */


defined('CON_FRAMEWORK') or die('Illegal call');


global $cfg;

// Use advanced mod_rewrites  ( 1 = yes, 0 = none )
$cfg['mod_rewrite']['use'] = 0;

// Path to the htaccess file with trailling slash from domain-root!
$cfg['mod_rewrite']['rootdir'] = '/';

// Check path to the htaccess file ( 1 = yes, 0 = none )
$cfg['mod_rewrite']['checkrootdir'] = 1;

// Start TreeLocation from Root Tree (set to 1) or get location from first category (set to 0)
$cfg['mod_rewrite']['startfromroot'] = 0;

// Prevent Duplicated Content, if startfromroot is enabled ( 1 = yes, 0 = none )
$cfg['mod_rewrite']['prevent_duplicated_content'] = 0;

// is multilanguage? ( 1 = yes, 0 = none )
$cfg['mod_rewrite']['use_language'] = 0;

// use language name in url? ( 1 = yes, 0 = none )
$cfg['mod_rewrite']['use_language_name'] = 0;

// is multiclient in only one directory? ( 1 = yes, 0 = none )
$cfg['mod_rewrite']['use_client'] = 0;

// use client name in url? ( 1 = yes, 0 = none )
$cfg['mod_rewrite']['use_client_name'] = 0;

// use lowercase url? ( 1 = yes, 0 = none )
$cfg['mod_rewrite']['use_lowercase_uri'] = 1;

// file extension for article links
$cfg['mod_rewrite']['file_extension'] = '';

// The percentage if the category name have to match with database names.
$cfg['mod_rewrite']['category_resolve_min_percentage'] = '100';

// Add start article name to url (1 = yes, 0 = none)
$cfg['mod_rewrite']['add_startart_name_to_url'] = 0;

// Default start article name to use, depends on active add_startart_name_to_url
$cfg['mod_rewrite']['default_startart_name'] = '';

// Rewrite urls on generating the code for the page. If active, the responsibility will be
// outsourced to moduleoutputs and you have to adapt the moduleoutputs manually. Each output of
// internal article/category links must be processed by using $sess->url. (1 = yes, 0 = none)
$cfg['mod_rewrite']['rewrite_urls_at_congeneratecode'] = 0;

// Rewrite urls on output of htmlcode at front_content.php. Is the old way, and doesn't require
// adapting of moduleoutputs. On the other hand usage of this way will be slower than rewriting
// option above. (1 = yes, 0 = none)
$cfg['mod_rewrite']['rewrite_urls_at_front_content_output'] = 1;


// Following five settings write urls like this one:
//     www.domain.de/category1-category2.articlename.html
// Changes of these settings causes a reset of all aliases, see Advanced Mod Rewrite settings in
// backend.
// NOTE: category_seperator and article_seperator must contain different character.

// Separator for categories
$cfg['mod_rewrite']['category_seperator'] = '/';

// Separator between category and article
$cfg['mod_rewrite']['article_seperator'] = '/';

// Word seperator in category names
$cfg['mod_rewrite']['category_word_seperator'] = '-';

// Word seperator in article names
$cfg['mod_rewrite']['article_word_seperator'] = '-';


// Routing settings for incomming urls. Here you can define routing rules as follows:
// $cfg['mod_rewrite']['routing'] = array(
//    '/a_incomming/url/foobar.html' => '/new_url/foobar.html',  # route /a_incomming/url/foobar.html to /new_url/foobar.html
//    '/cms/' => '/' # route /cms/ to / (doc root of client)
// );
$cfg['mod_rewrite']['routing'] = array();


// Redirect invalid articles to errorpage (1 = yes, 0 = no)
$cfg['mod_rewrite']['redirect_invalid_article_to_errorsite'] = 1;

