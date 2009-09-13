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

$table = 'cloak_data';
$query = "
    CREATE TABLE `cloak_data` (
        `id` int(11) NOT NULL auto_increment,
        `spider_name` varchar(255) NOT NULL default '',
        `record_type` enum('UA','IP') NOT NULL default 'UA',
        `value` varchar(255) NOT NULL default '',
        `is_user_defined` enum('N','Y') NOT NULL default 'N',
        PRIMARY KEY (`id`),
        KEY `value` (`value`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
        ";



/* Check table structure */
$result = Jojo::checkTable($table, $query);

/* Output result */
if (isset($result['created'])) {
    echo sprintf("jojo_simplecloak: Table <b>%s</b> Does not exist - created empty table.<br />", $table);
}

if (isset($result['added'])) {
    foreach ($result['added'] as $col => $v) {
        echo sprintf("jojo_simplecloak: Table <b>%s</b> column <b>%s</b> Does not exist - added.<br />", $table, $col);
    }
}

if (isset($result['different'])) Jojo::printTableDifference($table,$result['different']);