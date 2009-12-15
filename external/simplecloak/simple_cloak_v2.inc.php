<?php

$__metaRobotsExcludeProxiesCallbackHTML = '';

/*
// +----------------------------------------------------------------------+
// | SimpleCloakV2 Version 2                                                |
// | Class for cloaking content                                           |
// | http://www.SEOEgghead.com                                            |
// +----------------------------------------------------------------------+
// | Copyright (c) 2005-2006 Jaimie Sirovich and Cristian Darie           |
// +----------------------------------------------------------------------+
*/

// load configuration file
//require_once('config.inc.php');

class SimpleCloakV2
{

  function _connect()
  {
      if (USE_CUSTOM_CONNECT_CODE) return true;
    // Connect to MySQL server
    $dbLink = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD)
                    or die("Could not connect: " . mysql_error());

    // Connect to the seophp database
    mysql_select_db(DB_DATABASE) or die("Could not select database");

    return $dbLink;
  }

  function _close($dbLink)
  {
      if (USE_CUSTOM_CONNECT_CODE) return true;
    // close database connection
    mysql_close($dbLink);
  }

  // returns the confidence level
  function isSpider($spider_name = '', $check_uas = true, $check_ips = true, $use_user_defined_data = true, $ignore_bad_uas = true)
  {
    // default confidence level to 0
    $confidence = 0;

    // matching user agent?
    if ($check_uas)
      if (SimpleCloakV2::_get(0, $spider_name, 'UA', $_SERVER['HTTP_USER_AGENT'], '', $use_user_defined_data ? '' : 'N', $ignore_bad_uas ? 'bad' : ''))
        $confidence += 2;

    // matching IP?
    if ($check_ips)
      if (SimpleCloakV2::_get(0, $spider_name, 'IP', '', $_SERVER['REMOTE_ADDR'], $use_user_defined_data ? '' : 'N', $ignore_bad_uas ? 'bad' : ''))
        $confidence += 3;

    // return confidence level
    return $confidence;
  }

  // retrieve cloaking data filtered by the supplied parameters
  function _get($id = 0, $spider_name = '', $record_type = '',
               $value = '', $wildcard_value = '', $is_user_defined_data = '', $not_spider_name = '')
  {
    // by default, retrieve all records
    $q = " SELECT cloak_data.* FROM cloak_data WHERE TRUE ";

    // add filters
    if ($id) {
      $id = (int) $id;
      $q .= " AND id = $id ";
    }
    if ($spider_name) {
      $spider_name = mysql_escape_string($spider_name);
      $q .= " AND spider_name = '$spider_name' ";
    }
    if ($record_type) {
      $record_type = mysql_escape_string($record_type);
      $q .= " AND record_type = '$record_type' ";
    }
    if ($value) {
      $value = mysql_escape_string($value);
      $q .= " AND value = '$value' ";
    }
    if ($wildcard_value) {
      $wildcard_value = mysql_escape_string($wildcard_value);
      $q .= " AND ( '$wildcard_value' = value OR '$wildcard_value' LIKE CONCAT(value, '.%') ) ";
    }
    if ($is_user_defined_data) {
      $is_user_defined_data = mysql_escape_string($is_user_defined_data);
      $q .= " AND is_user_defined_data = '$is_user_defined_data' ";
    }
    if ($not_spider_name) {
      $not_spider_name = mysql_escape_string($not_spider_name);
      $q .= " AND spider_name != '$not_spider_name' ";
    }

    $dbLink = SimpleCloakV2::_connect();

    // execute the query
    $tmp = mysql_query($q);

    SimpleCloakV2::_close($dbLink);

    // return the results as an associative array
    $rows = array();
    while ($_x = mysql_fetch_assoc($tmp)) {
      $rows[] = $_x;
    }
    return $rows;
  }

  // updates the entire database with fresh spider data, but only if our data is
  // more than 7 days old, and if the online version from iplists.org has changed
  function updateAll($delete_user_defined_data = false)
  {

      $dbLink = SimpleCloakV2::_connect();

    // retrieve last update information from database
    $q = "SELECT cloak_update.* FROM cloak_update";
    $tmp = mysql_query($q);
    $updated = mysql_fetch_assoc($tmp);
    $db_version = $updated['version'];
    $updated_on = $updated ['updated_on'];

    // get the latest update more recent than 7 days, don't attempt an update
    if (isset($updated_on) &&
        (strtotime($updated_on) > strtotime("-604800 seconds")))
    {
      // close database connection
      SimpleCloakV2::_close($dbLink);
      // return false to indicate an update wasn't performed
      return false;
    }


    // read the latest iplists version
    $version_url = 'http://www.iplists.com/nw/version.php';

    $ch = curl_init();
    curl_setopt ($ch, CURLOPT_URL, $version_url);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
    $latest_version = curl_exec($ch);
    curl_close($ch);

    $latest_version = mysql_escape_string($latest_version);

    // if no updated version information was retrieved, abort
    if (!$latest_version)
    {
      // return false to indicate an update wasn't performed
      return false;
    }

    // save the update data
    $q = "DELETE FROM cloak_update";
    mysql_query($q);
    $q = "INSERT INTO cloak_update (version, updated_on) " .
         "VALUES('$latest_version', NOW())";
    mysql_query($q);

    // if we already have the current data, don't attempt an update
    if ($latest_version == $db_version)
    {
      // close database connection
      mysql_close($dbLink);
      // return false to indicate an update wasn't performed
      return false;
    }

    // update the database
    SimpleCloakV2::_updateCloakingDB('google',
                                  'http://www.iplists.com/nw/google.txt', $delete_user_defined_data);
    SimpleCloakV2::_updateCloakingDB('yahoo',
                                  'http://www.iplists.com/nw/inktomi.txt', $delete_user_defined_data);
    SimpleCloakV2::_updateCloakingDB('msn',
                                  'http://www.iplists.com/nw/msn.txt', $delete_user_defined_data);
    SimpleCloakV2::_updateCloakingDB('ask',
                                  'http://www.iplists.com/nw/askjeeves.txt', $delete_user_defined_data);
    SimpleCloakV2::_updateCloakingDB('altavista',
                                  'http://www.iplists.com/nw/altavista.txt', $delete_user_defined_data);
    SimpleCloakV2::_updateCloakingDB('lycos',
                                  'http://www.iplists.com/nw/lycos.txt', $delete_user_defined_data);
    SimpleCloakV2::_updateCloakingDB('wisenut',
                                  'http://www.iplists.com/nw/wisenut.txt', $delete_user_defined_data);

    // close connection
    SimpleCloakV2::_close($dbLink);

    // return true to indicate a successful update
    return true;
  }

  // update the database for the mentioned spider, by reading the provided URL
  function _updateCloakingDB($spider_name, $url, $delete_user_defined_data = false)
  {

      $ua_regex = '/^# UA "(.*)"$/m';
      $ip_regex = '/^([0-9.]+)$/m';

    // use cURL to read the data from $url
    // NOTE: additional settings are required when accessing the web through a proxy
    $ch = curl_init();
    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_HEADER, 1);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
    $result = curl_exec($ch);
    curl_close($ch);

    // use _parseListURL to parse the list of IPs and user agents
    $lists = SimpleCloakV2::_parseListURL($result, $ua_regex, $ip_regex);

    // if the user agents and IPs weren't retrieved, we cancel the update
    if (!$lists['ua_list'] || !$lists['ip_list']) return;

    // lock the cloack_data table to avoid concurrency problems
    mysql_query('LOCK TABLES cloak_data WRITE');

    // delete all the existing data for $spider_name
    SimpleCloakV2::_deleteSpiderData($spider_name, $delete_user_defined_data ? '' : 'N');

    // insert the list of user agents for the spider
    foreach ($lists['ua_list'] as $ua) {
      SimpleCloakV2::_insertSpiderData($spider_name, 'UA', $ua);
    }

    // insert the list of IPs for the spider
    foreach ($lists['ip_list'] as $ip) {
      SimpleCloakV2::_insertSpiderData($spider_name, 'IP', $ip);
    }

    // release the table lock
    mysql_query('UNLOCK TABLES');
  }

  // helper function used to parse lists of user agents and IPs
  function _parseListURL($data, $ua_regex, $ip_regex)
  {
    $ua_list_ret = preg_match_all($ua_regex, $data, $ua_list);
    $ip_list_ret = preg_match_all($ip_regex, $data, $ip_list);
    return array('ua_list' => $ua_list[1], 'ip_list' => $ip_list[1]);
  }

  // inserts a new row of data to the cloaking table
  function _insertSpiderData($spider_name, $record_type, $value, $is_user_defined = 'N')
  {
    // escape input data
    $spider_name = mysql_escape_string($spider_name);
    $record_type = mysql_escape_string($record_type);
    $value = mysql_escape_string($value);
    $is_user_defined = mysql_escape_string($is_user_defined);

    // build and execute the INSERT query
    $q  = "INSERT INTO cloak_data (spider_name, record_type, value, is_user_defined) " .
          "VALUES ('$spider_name', '$record_type', '$value', '$is_user_defined')";

    mysql_query($q);
  }

  // delete the cloaking data for the mentioned spider
  function _deleteSpiderData($spider_name, $is_user_defined = '')
  {
    // escape input data
    $spider_name = mysql_escape_string($spider_name);

    // build and execute the DELETE query
    $q = "DELETE FROM cloak_data WHERE spider_name='$spider_name'";

    if ($is_user_defined) {
      $is_user_defined = mysql_escape_string($is_user_defined);
      $q .= " AND is_user_defined = '$is_user_defined' ";
    }

    mysql_query($q);
  }

  // only use if it's not found via the IPLists cloaking database
  function botVerifyByDNS($ua = array('google', '#.*\.googlebot\.com$#'))
  {

      // check cache of bad bots
      if (SimpleCloakV2::isSpider('bad', false, true, true, false)) {
          return false;
      }

      // check only UA since this function is only called if the cloaking DB doesn't handle it
      if (SimpleCloakV2::isSpider($ua[0], true, false)) {
          // reverse lookup
          $host_name = gethostbyaddr($_SERVER['REMOTE_ADDR']);

          // if it says it's a certain UA but gethostbyaddr the corresponding domain regex, store it and then abort
          if (!preg_match($ua[1], $host_name)) {
              $dbLink = SimpleCloakV2::_connect();
              SimpleCloakV2::_insertSpiderData('bad', 'IP', $_SERVER['REMOTE_ADDR'], 'Y');
              SimpleCloakV2::_close($dbLink);
              return false;
          }

          $connected_ip_address = $_SERVER['REMOTE_ADDR'];
          $host_name_ip_address = gethostbyname($host_name);

          // if the connected IP matches the authoritative IP, we have a match
          if ($connected_ip_address == $host_name_ip_address) {
              $dbLink = SimpleCloakV2::_connect();
              SimpleCloakV2::_insertSpiderData($ua[0], 'IP', $_SERVER['REMOTE_ADDR'], 'Y');
              SimpleCloakV2::_close($dbLink);
              return true;
          } else {
              // if it says it's a certain UA, gethostbyaddr says the right thing, but gethostbyname does not
              $dbLink = SimpleCloakV2::_connect();
              SimpleCloakV2::_insertSpiderData('bad', 'IP', $_SERVER['REMOTE_ADDR'], 'Y');
              SimpleCloakV2::_close($dbLink);
              return false;
          }
      }

      // it does not even say it's a bot via UA
      return false;

  }

  function _addMetaRobotsExcludeProxiesCallback($buffer)
  {
      global $__metaRobotsExcludeProxiesCallbackHTML;
      return preg_replace('#</title>#', '</title>' . $__metaRobotsExcludeProxiesCallbackHTML, $buffer);
  }

  function metaRobotsExcludeProxies($auto_modify_content = true, $uas = array(array('google', '#.*\.googlebot\.com$#'), array('yahoo', '#.*\.yahoo\.net$#'), array('msn', '#.*\.live\.com$#'), array('ask', '#.*\.ask.com$#') ), $meta_tag = '<meta name="robots" content="noindex,nofollow" />', $passlist_regex = '')
  {
      global $__metaRobotsExcludeProxiesCallbackHTML;

      if ($meta_tag)
          $__metaRobotsExcludeProxiesCallbackHTML = $meta_tag;

      // if it's on our passlist
      // ex: #become|lycos|somestupidbot#
      if ($passlist_regex) {
          if (preg_match($passlist_regex, $_SERVER['HTTP_USER_AGENT'])) return false;
      }

      foreach ($uas as $u) {

          // if it's a bot according to UA, then start to investigate
          if (SimpleCloakV2::isSpider($u[0], true, false)) {

              // if it's a bot according to IPLists or our user-defined list
              if (SimpleCloakV2::isSpider($u[0], false, true)) {
                  $log = new Jojo_Eventlog();
                  $log->code = 'simplecloak';
                  $log->importance = 'normal';
                  $log->shortdesc = 'Spider identified by IP lists';
                  $log->desc = $_SERVER['HTTP_USER_AGENT'].' '.Jojo::getIP();
                  $log->savetodb();
                  unset($log);
                  return false;
              // if it's a bot according to DNS
              } else if (SimpleCloakV2::botVerifyByDNS($u)) {
                  $log = new Jojo_Eventlog();
                  $log->code = 'simplecloak';
                  $log->importance = 'normal';
                  $log->shortdesc = 'Spider identified by DNS';
                  $log->desc = $_SERVER['HTTP_USER_AGENT'].' '.Jojo::getIP();
                  $log->savetodb();
                  unset($log);

                  return false;
              // if it's not
              } else {
                  $log = new Jojo_Eventlog();
                  $log->code = 'simplecloak';
                  $log->importance = 'normal';
                  $log->shortdesc = 'Spider not identified by IP lists or DNS';
                  $log->desc = $_SERVER['HTTP_USER_AGENT'].' '.Jojo::getIP();
                  $log->savetodb();
                  unset($log);

                  if ($auto_modify_content) ob_start(array('SimpleCloakV2', '_addMetaRobotsExcludeProxiesCallback'));
                  return true;
              }

          }

      }

    // it's not a bot according to UA
    if ($auto_modify_content) ob_start(array('SimpleCloakV2', '_addMetaRobotsExcludeProxiesCallback'));
    return true + 1;

  }

}
?>