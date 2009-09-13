<?php
/**
 *                    Jojo CMS
 *                ================
 *
 * Copyright 2007 Harvey Kane <code@ragepank.com>
 * Copyright 2007 Michael Holt <code@gardyneholt.co.nz>
 * Copyright 2007 Melanie Schulz <mel@gardyneholt.co.nz>
 *
 * See the enclosed file license.txt for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Harvey Kane <code@ragepank.com>
 * @license http://www.fsf.org/copyleft/lgpl.html GNU Lesser General Public License
 * @link    http://www.jojocms.org JojoCMS
 */

/* intercept meta tags before sending to smarty */
Jojo::addHook('before_fetch_template', 'before_fetch_template', 'jojo_simplecloak');

/* intercept cached output so meta robots tags can be hacked if needed */
Jojo::addFilter('cached_content', 'cached_content', 'jojo_simplecloak');

/* add option for enabling / disabling simplecloak */
$_options[] = array(
    'id' => 'simplecloak_enabled',
    'category' => 'SEO',
    'label' => 'Enable SimpleCloak',
    'description' => 'Enable this option at your own risk. Read the docs before turning this puppy on.',
    'type' => 'radio',
    'default' => 'no',
    'options' => 'yes,no',
    'plugin' => 'jojo_simplecloak'
);