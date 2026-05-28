<?php

/*
 * $Id: function.ppurl.php,v 1.2 2005/07/05 14:30:07 pkremer Exp $
 *
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.ppurl.php
 * Type:     function
 * Name:     ppurl
 * Version:  1.3
 * Date:     July 5th, 2005
 * Purpose:  print an URL with added/deleted key/value pairs
 *           Example:
 *           {ppurl url="index.php?l=EN" key="print" value="1"}
 *           prints the string "index.php?l=EN&print=1"
 * Params:   url:    string, any URL which should be worked on
 *           key:    string, name of a GET parameter to change
 *           value:  string, value for GET parameter 'key'
 *           path:   string, specifies a new path for URL
 *           prefix: string, prefixed before 'path'
 * Hints:    - if url is omitted, the current URL (including GET) is taken
 *           - if value for a key is omitted, the key is deleted, e.g.:
 *             {ppurl url="index.php?print=1" key="print"}
 *             prints the string "index.php"
 *           - if path is omitted, basename('url') is used
 *           - prefix is handy if you use a template variable for absolute
 *             URL's instead of relative URL's
 *           - new parameters get urlencoded automatically
 * Bugs:     The whole thing only works if you use '&' as seperator for GET
 *           parameters, '&amp;' will _not_ work. (In fact this is not a bug,
 *           as the URL generated is syntactically correct, but not
 *           syntactically correct html)
 * Install:  Drop into the plugin directory.
 *           (requires shared.url_parameters.php)
 * Author:   Paul Kremer (pkremer !At! spurious !DOT! biz)
 * -------------------------------------------------------------
 */

function smarty_function_ppurl($params, &$smarty)
{
    extract($params);
    if (!isset($url)) {
        $nurl = new Url_Parameters();
    } else {
        $nurl = new Url_Parameters($url);
    }
    # Jan Mensik - support for cool uri
    if (empty($url) && $cooluri) {
        if (in_array('noparams', array_keys($params))) {
            $nurl->fromCurrentCoolUri(false);
        } else {
            $nurl->fromCurrentCoolUri(true);
        }
    } elseif (empty($url)) {
        if (in_array('noparams', array_keys($params))) {
            $nurl->fromCurrent(false);
        } else {
            $nurl->fromCurrent(true);
        }
    }

    if (!in_array('value', array_keys($params))) {
        if (isset($key)) {
            $nurl->setParameter($key, false);
        }
    } else {
        if (!in_array('key', array_keys($params))) {
            $smarty->trigger_error("assign: missing 'key' parameter");
            return;
        }

        $nurl->setParameter($key, $value);
    }

    # Jan Mensik - remove
    if (in_array('remove', array_keys($params))) {
        $remove = explode('&', $remove);
        foreach ($remove as $temp) {
            $nurl->setParameter($temp, false);
        }
    }

    # Jan Mensik - add
    if (in_array('add', array_keys($params))) {
        $add = explode('&', $add);
        foreach ($add as $temp) {
            $nurl->setParameter($temp, 1);
        }
    }

    if (!in_array('path', array_keys($params))) {
        $path = $nurl->getBasename();
    }

    if (in_array('prefix', array_keys($params))) {
        $path = $prefix . $path;
    }

    if ($path != '') {
        $nurl->setBasename($path);
    }

    # Jan Mensik - tolowercase
    if (in_array('tolowercase', array_keys($params))) {
        return strtolower($nurl->getUrl());
    } else {
        return $nurl->getUrl();
    }
}
