<?php
/** $Id: FastFrame.php,v 1.2 2003/01/08 00:08:51 jrust Exp $ */
// {{{ includes

require_once dirname(__FILE__) . '/FastFrame/Registry.php';
require_once dirname(__FILE__) . '/FastFrame/Error.php';

// }}}
// {{{ class FastFrame

/**
 * The FastFrame class provides the functionality shared by all FastFrame 
 * applications, such as creating URL's, getting a temporary file, etc.
 * This is a completely static class, all methods can be called directly.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lesser.html.
 *
 * @version Revision: 2.0 
 * @author  Horde  http://www.horde.org/
 * @author  Jason Rust <jrust@bsiweb.com>
 * @author  Dan Allen <dan@mojavelinux.com>
 * @access  public
 * @package FastFrame
 */

// }}}
class FastFrame {
    // {{{ string  url()

    /**
     * Return a session-id-ified version of $uri.
     *
     * @param  string $in_url The URL to be modified
     * @param  array  $in_queryVars (optional) Set of variable = value pairs
     *         ...
     * @param  bool   $in_full (optional) generate a full url?
     *
     * @return string The url with the session id appended
     */
    function url($in_url)
    {
        // just need to add to get rid of duplicate '/'

        $o_registry =& FastFrame_Registry::singleton();

        // merge all the get variable arrays
        $argv = func_get_args();
        // pop off the url and the full flag
        array_shift($argv);

        $last = end($argv);
        if (!is_array($last)) {
            $in_full = (bool) array_pop($argv);
        }
        else {
            $in_full = false;
        }

        // if it begins with javascript then leave as is
        if (strpos($in_url, 'javascript:') === 0) {
            return $in_url;
        }

        // if we have a '/' we assume this is an absolute link
        // or if it begins with http we assume it's a full link already
        if (strpos($in_url, '/') === 0 ||
            preg_match(':^https?\://:', $in_url)) {
            $url = $in_url;
        }
        // we need to just add the web part of the path
        else {
            $url = $o_registry->getAppParam('web_base') . '/' . $in_url;
        }

        // see if we need to make this a full query string
        if ($in_full &&
            !preg_match(':^https?\://:', $in_url)) {
            $url = ($o_registry->getConfigParam('server/use_ssl') ? 'https' : 'http') . '://' . $o_registry->getConfigParam('server/hostname') . $url;
        }

        // getVars will be a name=>value pair hash of get variables
        $getVars = array();
        foreach($argv as $getVarList) {
            settype($getVarList, 'array');
            // if we are dealing with one of the gpc arrays, then disable magic quotes
            if ($getVarList === $_GET || $getVarList === $_POST || $getVarList === $_COOKIE) {
                $getVarList = FastFrame::disableMagicQuotes($getVarList);    
            }
            $getVars = array_merge($getVars, $getVarList);
        }

        // add the session to the array list if it is configured to do so
        if ($o_registry->getConfigParam('session/append')) {
            $sessName = $o_registry->getConfigParam('session/name');
            // make sure it was not already set (like set to nothing)
            if (!isset($getVars[$sessName])) {
                $getVars[$sessName] = session_id();
            }
            // if it was set to false then remove it
            elseif (empty($getVars[$sessName])) {
                unset($getVars[$sessName]);
            }
        }

        // urlencode the values as an array with values name=urlencode(value)
        settype($getPairs, 'array');
        foreach($getVars as $name => $value) {
            // make sure we did not end up with an empty name
            if (!empty($name)) {
                $getPairs[] = $name . '=' . urlencode($value);
            }
        }

        $queryString = implode(ini_get('arg_separator.output'), $getPairs);

        if (!empty($queryString)) {
            $url .= '?' . $queryString;
        }

        return $url;
    }
    
    // }}}
    // {{{ void    purl()

    /**
     * Print a session-id-ified version of the URI.
     *
     * @param  string $in_url The URL to be modified
     * @param  array  $in_queryVars (optional) Set of variable = value pairs
     *         ...
     * @param  bool   $in_full (optional) whether to generate a full url
     */
    function purl($in_url)
    {
        $argv = func_get_args();
        echo call_user_func_array(array('FastFrame', 'url'), $argv);
    }

    // }}}
    // {{{ string  selfURL()

    /**
     * Return a session-id-ified version of $PHP_SELF.
     *
     * @param  array  $in_queryVars (optional) Set of variable = value pairs
     *         ...
     * @param bool  $in_full whether to generate a full or partial link
     *
     * @access public
     * @return string The URL
     */
    function selfURL()
    {
        // get the arguments passed in
        $argv = func_get_args();

        // add on the url to the beginning
        array_unshift($argv, $_SERVER['PHP_SELF']);
        
        return call_user_func_array(array('FastFrame', 'url'), $argv);
    }

    // }}}
    // {{{ void    pselfURL()

    /**
     * Print a session-id-ified version of $PHP_SELF.
     *
     * @param  array  $in_queryVars (optional) Set of variable = value pairs
     *         ...
     * @param bool  $in_full whether to generate a full or partial link
     *
     * @access public
     * @return string The URL
     */
    function pselfURL()
    {
        $argv = func_get_args();
        echo call_user_func_array(array('FastFrame', 'selfURL'), $argv);
    }

    // }}}
    // {{{ void    redirect()
    
    /**
     * Given a url, send the headers to redirect to that site.
     *
     * Perform the necessary action to cause the page to reload with the
     * new url as the location.
     *
     * @param  string $in_url new location
     *
     * @access public
     * @return void
     */
    function redirect($in_url)
    {
        header('Location: ' . $in_url, true);
        exit;
    }

    // }}}
    // {{{ void    fatal

    /**
     * Abort with a fatal error, displaying debug information to the
     * user.
     *
     * @param mixed     $in_message Either a string or a PEAR_Error object.
     * @param int       $in_file The file in which the error occured.
     * @param int       $in_line The line on which the error occured.
     * @param bool      $in_log (optional) Log this message via Horde::logMesage()?
     *
     * @access public
     * @return void
     */
    function fatal($in_message, $in_file, $in_line, $in_log = true)
    {
        $s_errortext = _('<b>A fatal error has occurred:</b>') . "<br /><br />\n";
        if (PEAR::isError($in_message)) {
            $s_errortext .= $in_message->getMessage() . "<br /><br />\n";
        }
        else {
            $s_errortext .= $in_message . "<br /><br />\n";
        }

        $s_errortext .= sprintf(_('[line %s of %s]'), $in_line, $in_file);

        if ($in_log) {
            $s_errortext .= "<br /><br />\n";
            $s_errortext .= _('Details have been logged for the administrator.');
        }

        // Log the fatal error via FastFrame::logMessage() if requested.
        if ($in_log) {
            FastFrame::logMessage($in_message, $in_file, $in_line, LOG_EMERG);
        }

        // Hardcode a small stylesheet so that this doesn't depend on
        // anything else.
        echo <<< HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>FastFrame :: Fatal Error</title>
<style type="text/css">
  body { font-family: Geneva,Arial,Helvetica,sans-serif; font-size: 12px; background-color: #222244; color: #ffffff; }
  .header { color: #ccccee; background-color: #444466; font-family: Verdana,Helvetica,sans-serif; font-size: 12px; }
</style>
</head>
<body>
<table border="0" align="center" width="500" cellpadding="2" cellspacing="0">
<tr><td class="header" align="center">$s_errortext</td></tr>
</table>
</body>
</html>
HTML;

        exit;
    }

    // }}}
    // {{{ void    logMessage()

    /**
     * Log a message to the global FastFrame log backend.
     *
     * @access public
     *
     * @param mixed     $in_message Either a string or a PEAR_Error object.
     * @param string    $in_file What file was the log function called from (e.g. __FILE__) ?
     * @param int       $in_line What line was the log function called from (e.g. __LINE__) ?
     * @param int       $in_priority  (optional) The priority of the message. One of: LOG_EMERG,
     *                                LOG_ALERT, LOG_CRIT, LOG_ERR, LOG_WARNING, LOG_NOTICE,
     *                                LOG_INFO, LOG_DEBUG
     *
     * @access public
     * @return bool True if logging succeeded, false otherwise
     */
    function logMessage($in_message, $in_file, $in_line, $in_priority = LOG_INFO)
    {
        static $o_logger;
        $o_registry =& FastFrame_Registry::singleton();
        
        if (!$o_registry->getConfigParam('log/enabled') || 
            $in_priority > $o_registry->getConfigParam('log/priority') ||
            !is_readable('Log/Log.php')) {
            return false;
        }

        if (!isset($o_logger)) {
            include_once 'Log/Log.php';

            if (is_null($s_type = $o_registry->getConfigParam('log/type')) ||
                is_null($s_name = $o_registry->getConfigParam('log/name')) ||
                is_null($s_ident = $o_registry->getConfigParam('log/ident')) ||
                is_null($a_params = $o_registry->getConfigParam('log/params'))) {
                PEAR::raiseError(null, FASTFRAME_NOT_CONFIGURED, E_USER_WARNING, 'FastFrame is not correctly configured to log error messages.  You must configure at least a text file in the conf.php file', 'FastFrame_Error', true);
            }

            $o_logger = Log::singleton($s_type, $s_name, $s_ident, $a_params);
        }

        if (PEAR::isError($in_message)) {
            $s_userinfo = $in_message->getUserInfo();
            $s_message = $in_message->getMessage();
            if (!empty($s_userinfo)) {
                $s_message .= ': ' . $s_userinfo;
            }
        }

        $s_message = '[' . $o_registry->getCurrentApp() . '] ' . $s_message . ' [on line ' . $in_line .
' of "' . $in_file . '"]';

        $o_logger->log($s_message, $in_priority);  

        return true;
    }

    // }}}
    // {{{ int     bytesToHuman()

    /**
     * Converts a number of bytes into a human readable
     * string of kb, mb, or gb depending on what is appropriate
     *
     * @param double $in_bytes The value to format
     * @param integer $in_dec The number of decimals to retain
     * @param integer $in_sens The sensitiveness.  The bigger the number
     *                          the larger the step to the next unit
     *
     * @access public
     * @return string Formatted number string
     */
    function bytesToHuman($in_bytes, $in_dec = 0, $in_sens = 3)
    {
        // the different formats
        $byteUnits[0] = _('Bytes');
        $byteUnits[1] = _('KB');
        $byteUnits[2] = _('MB');
        $byteUnits[3] = _('GB');

        $li           = pow(10, $in_sens);
        $dh           = pow(10, $in_dec);
        $unit         = $byteUnits[0];

        if ($in_bytes >= $li*1000000) {
            $value = round($in_bytes/(1073741824/$dh))/$dh;
            $unit  = $byteUnits[3];
        }
        else if ($in_bytes >= $li*1000) {
            $value = round($in_bytes/(1048576/$dh))/$dh;
            $unit  = $byteUnits[2];
        }
        else if ($in_bytes >= $li) {
            $value = round($in_bytes/(1024/$dh))/$dh;
            $unit  = $byteUnits[1];
        }
        else {
            $value = $in_bytes;
            $in_dec = 0;
        }

        $returnValue = number_format($value, $in_dec);

        return $returnValue.' '.$unit;
    }

    // }}}
    // {{{ string  getTmpDir()

    /**
     * Determine the location of the system temporary directory.
     * If a specific setting cannot be found, it defaults to /tmp
     *
     * @param  string $in_dir tmpdir override
     *
     * @return string    A directory name which can be used for temp files
     *                   or false if one could not be found
     * [!] if an override is given, it falls back to system tmp instead of app tmp [!]
     */
    function getTmpDir($in_dir = null)
    {
        $registry =& FastFrame_Registry::singleton();

        $checkDir = is_null($in_dir) ? $registry->getConfigParam('general/tmpdir') : $in_dir;

        return File::getTempDir($checkDir);
    }

    // }}}
    // {{{ string  getTmpFile()

    /**
     * Create a temporary filename for the lifetime of the script, and
     * (optionally) register it to be deleted at request shutdown.
     *
     * @param string $prefix  Prefix to make the temporary name more recognizable
     * @param optional boolean $delete Delete the file at the end of the request?
     * @param optional string $dir Directory to create the temporary file in
     * @return string         containing the full path-name to the temporary file
     *                        or false if a temp file could not be created
     */
    function getTmpFile($in_prefix = 'FastFrame-', $in_delete = true, $in_dir = null)
    {
        if (File::isError($tmpDir = FastFrame::getTmpDir($in_dir))) {
            return $tmpDir;
        }

        return File::getTempFile($in_prefix, $in_delete, $tmpDir); 
    }
    
    // }}}
    // {{{ mixed   getCGIParam()

    /**
     * Get the the gpc variable.
     *
     * Since servers have gpc_magic_quotes enabled, we need to make an easy way to
     * strip them off when we grab form data for display.  
     *
     * @param  string $in_var name of the variable
     * @param  string $in_type (optional) type of variable (get, post, cookie or request (all))
     * @param  string $in_default (optional) default value if no value is found
     *
     * @access public
     * @return string value of the variable or default value
     */
    function getCGIParam($in_var, $in_type = 'gpc', $in_default = null, $in_callback = null, $in_args = array(), $in_index = 1)
    {
        // handle variables that are arrays
        if ($pos = strpos($in_var, '[')) {
            $var = '[\'' . substr($in_var, 0, $pos) . '\']' . substr($in_var, $pos);
        }
        else {
            $var = '[\'' . $in_var . '\']';
        }

        if (stristr($in_type, 's') && eval("return isset(\$_SESSION$var);")) {
            $data = FastFrame::disableMagicQuotes(eval("return \$_SESSION$var;")); 
        }
        elseif (stristr($in_type, 'c') && eval("return isset(\$_COOKIE$var);")) {
            $data = FastFrame::disableMagicQuotes(eval("return \$_COOKIE$var;")); 
        }
        elseif (stristr($in_type, 'p') && eval("return isset(\$_FILES$var);")) {
            $data = FastFrame::disableMagicQuotes(eval("return \$_FILES$var;")); 
        }
        elseif (stristr($in_type, 'p') && eval("return isset(\$_POST$var);")) {
            $data = FastFrame::disableMagicQuotes(eval("return \$_POST$var;")); 
        }
        elseif (stristr($in_type, 'g') && eval("return isset(\$_GET$var);")) {
            $data = FastFrame::disableMagicQuotes(eval("return \$_GET$var;")); 
        }
     
        if (!isset($data)) {
            $data = $in_default;
        }
        elseif (!is_null($in_callback)) {
            array_splice($in_args, $in_index - 1, $in_index - 1, $data);
            $data = call_user_func_array($in_callback, $in_args);
        }

        return $data;
    }

    // }}}
    // {{{ string  disableMagicQuotes()

    /**
     * If magic_quotes_gpc is in use, strip the slashes
     *
     * Some servers have strings automatically escaped when submitted from a form.
     * Here will strip any that exist on a non-array
     *
     * @param  mixed $in_var The string or array to un-quote, if necessary.
     *
     * @access public
     * @return mixed variable minus any magic quotes.
     */
    function disableMagicQuotes($in_var)
    {
        static $magic_quotes_enabled;

        if (!isset($magic_quotes_enabled)) {
            $magic_quotes_enabled = get_magic_quotes_gpc();
        }

        if ($magic_quotes_enabled) {
            if (is_array($in_var)) {
                $in_var = array_deep_map($in_var, 'stripslashes');
            }
            else {
                $in_var = stripslashes($in_var);
            }
        }

        return $in_var;
    }

    // }}}
    // {{{ void    unsetCookies()

    /**
     * Clear the values of a cookie list
     *
     * Since there is no clean way to do this in php, we provide this
     * function to force set the cookie to a zero value and in the past.
     *
     * @param  mixed  $in_name string name of the cookie or array of string names
     *
     * @access public
     * @return void
     */
    function unsetCookies($in_names, $in_path = '/', $in_domain = '') 
    {
        foreach ((array) $in_names as $name) {
            setcookie($name, 'null', time(), $in_path, $in_domain); 
            unset($_COOKIE[$name]);
        }
    }

    // }}}
    // {{{ void    setCookies()

    /**
     * Set each of the cookies.
     *
     * Run through the associative array and set the cookies, running the php
     * cookie function an additionally adding them to the $_COOKIE array for
     * immediate use
     *
     * @param  array  $in_nameValues name => value pairs of the cookies to be set
     * @param  int    $in_expire (optional) when the cookie will expire
     * @param  string $in_path (optional) path for the cookie
     * @param  string $in_domain (optional) domain for the cookie
     *
     * @access public
     * @return void
     */
    function setCookies($in_nameValues, $in_expire = 0, $in_path = '/', $in_domain = '')
    {
        foreach((array) $in_nameValues as $name => $value) {
            setcookie($name, $value, $in_expire, $in_path, $in_domain); 
            $_COOKIE[$name] = $value;
        }
    }

    // }}}
    // {{{ string  checkUniquenessJsFunc()

    /**
     * Returns a javascript array and function which will check 
     * to make sure an element is unique.
     *
     * @param array $currentElements Array of current elements
     * @param optional string $myname What myname to allow from the list 
     *                      This is useful if you want to allow the current myname 
     * @param optional string $arrayName The name of the javascript array
     * @param optional string $funcName The name of the javascript function
     * @param optional bool $in_caseInsensitive Should the check be case insensitive?
     *
     * @access public
     * @return string The javascript function
     */
    function checkUniquenessJsFunc($currentElements, $myname = null, $arrayName = 'uniquenessArray', $funcName = 'check_uniqueness', $in_caseInsensitive = true)
    {
        $js = "
<script language=\"JavaScript\">
var $arrayName = new Array();";
        settype($currentElements, 'array');
        foreach ($currentElements as $element) {
            if ($myname == $element && $myname != null) {
                $bool = 'false';
            }
            else {
                $bool = 'true';
            }
            
            if ($in_caseInsensitive) {
                $element = strtolower($element);
            }

            $js .= "\n{$arrayName}['$element'] = $bool;";
        }


        $js .= "
function $funcName(field_name, value) {";
        if ($in_caseInsensitive) {
            $js .= '
    value = value.toLowerCase();';
        }

        $js .= "
    if ({$arrayName}[value]) {
        return false;
    }
    else {
        return true;
    }
}
</script>
";
        return $js;
    }

    // }}}
    // {{{ string  getRandomString()

    /**
     * Generates a random string
     *
     * @param int $in_length The length of the string 
     * @param string $in_possibleChars (optional) Specify characters to use
     *
     * @access public
     * @return string The random string
     */
    function getRandomString($in_length, $in_possibleChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
    {
        $possibleCharsLength = strlen($in_possibleChars);
        $string = '';
        while(strlen($string) < $in_length) {
            $string .= substr($in_possibleChars, (mt_rand() % $possibleCharsLength), 1);
        }
        return $string;
    }
    
    // }}}
    // {{{ string  prepareEmailURL()

    /**
     * Prepares a link to be sent in an e-mail
     * by checking if the e-mail address is from AOL
     *
     * @param string $in_email The e-mail address
     * @param string $in_url The URL
     * @param bool $in_isEmail (optional) Is the URL an e-mail address?
     *
     * @access public
     * @return string The e-mail URL
     */
    function prepareEmailURL($in_email, $in_url, $in_isEmail = false)
    {
        if (preg_match(':aol\.com$:', $in_email)) {
            if ($in_isEmail) {
                $link = "<a href=\"mailto:$in_url\">$in_url</a>";
            }
            else {
                $link = "<a href=\"$in_url\">$in_url</a>";
            }
        }
        else {
            $link = $in_url;
        }
        
        return $link;
    }

    // }}}
    // {{{ bool    recursiveRmdir()

    /**
     * Deletes a directory and all files in it
     *
     * @param string $in_dir The full path to the directory
     *
     * @access public
     * @return bool Result of rmdir
     */
    function recursiveRmdir($in_dir)
    {
        if(@!$opendir = opendir($in_dir)) {
            return false;
        }

        while (false !== ($readdir = readdir($opendir))) {
            if ($readdir !== '..' && $readdir !== '.') {
                $readdir = trim($readdir);
                if (is_file($in_dir.'/'.$readdir)) {
                    if (@!unlink($in_dir.'/'.$readdir)) {
                        return false;
                    }
                } 
                elseif (is_dir($in_dir.'/'.$readdir)) {
                    // Calls itself to clear subdirectories
                    if (!FastFrame::recursiveRmdir($in_dir.'/'.$readdir)) {
                        return false;
                    }
                }
            }
        }

        closedir($opendir);

        if (@!rmdir($in_dir)) {
            return false;
        }

        return true;
    }

    // }}}
    // {{{ bool    recursiveFind()

    /**
     * Finds all files in a directory that match the passed
     * in regular expression
     *
     * @param string $in_dir The full path to the directory
     * @param string $in_match The perl regular expression.  * can be passed if you want all files
     * @param bool $in_clearCache Clear the cache of files found?
     *
     * @access public
     * @return array The array of found files or false if an error was encountered 
     */
    function recursiveFind($in_dir, $in_match, $in_clearCache = true)
    {
        static $foundFiles;

        if ($in_clearCache) {
            $foundFiles = array();
        }

        if(@!$opendir = opendir($in_dir)) {
        }

        // prepare match string
        if ($in_match != '*') {
            $match = preg_quote($in_match, ':');
        }
        else {
            $match = $in_match;
        }

        while (false !== ($readdir = readdir($opendir))) {
            if ($readdir !== '..' && $readdir !== '.') {
                $readdir = trim($readdir);
                if (is_file($in_dir.'/'.$readdir)) {
                    if ($in_match == '*' || preg_match(":$in_match:", $readdir)) {
                        $foundFiles[] = $in_dir.'/'.$readdir;
                    }
                    continue;
                } 
                elseif (is_dir($in_dir.'/'.$readdir)) {
                    // recursive call to find files within directory 
                    $tmp_files = FastFrame::recursiveFind($in_dir.'/'.$readdir, $in_match, false);
                    if ($tmp_files === false) {
                        return false;
                    }
                    else {
                        $foundFiles += $tmp_files;
                    }
                }
            }
        }

        closedir($opendir);

        return $foundFiles;
    }

    // }}}
    // {{{ bool    mkdirp()

    /**
     * Make parent directories as needed and return if directory exists
     *
     * Attempted to make the directory requested and if any stage fails
     * it returns false.
     *
     * @param  string $path directory path to create/check
     * @param  mode $mode (optional) mode to create the directory in
     */
    function mkdirp($path, $mode = 0775)
    {
        // check for error condition
        if (strlen($path) == 0) {
            return false;
        }

        // don't create a directory if it already exists
        if (@is_dir($path)) {
            return true;
        }

        return FastFrame::mkdirp(dirname($path)) && @mkdir($path, $mode);
    }

    // }}}
    // {{{ array   createNumericSelect()

    /**
     * Creates a numeric array based on a start and end value
     *
     * @param int $in_startNum The start number
     * @param int $in_endNum The end number
     *
     * @access public
     * @return array A sequential array of numbers
     */
    function createNumericSelect($in_startNum, $in_endNum)
    {
        $select = array();
        for ($i = $in_startNum; $i <= $in_endNum; $i++) {
            $select[$i] = $i;
        }

        return $select;
    }

    // }}}
    // {{{ bool    streamData

    /**
     * Streams file/data to the client so that they can download it
     *
     * @param string $in_data The data string or filename
     * @param string $in_type (optional) The type
     * @param string $in_filename (optional) The filename
     * @param string $in_mimetype (optional) If not given we try to determine it
     *
     * @access public
     * @return bool True if they finished downloading, false otherwise
     */
    function streamData($in_data, $in_type = 'file', $in_filename = 'data.txt', $in_mimetype = null)
    {
        $status = false;
        $chunkLength = 1024 * 8;
        $hoursTillExpire = 2;

        // first check connection status and file existence
        if (connection_status() != 0 || ($in_type == 'file' && !file_exists($in_data))) {
            return false;
        }

        if ($in_type == 'file') {
            if ($in_filename != 'data.txt') {
                $filename = $in_filename;
            }
            else {
                $filename = basename($in_data);
            }

            $filesize = filesize($in_data);
        }
        else {
            $filename = $in_filename; 
            $filesize = strlen($in_data);
        }

        if (is_null($in_mimetype)) {
            require_once $GLOBALS['registry']->getModuleFile('Download.php', 'standardForm', 'libs');
            $downloads =& new Standardform_Download();
            $mimetype = $downloads->getMimeType($filename);
        }
        else {
            $mimetype = $in_mimetype;
        }

        // decide which mimes we try to load straight into the browser and which we force them to download
        if ($mimetype == 'application/pdf') {
            $disposition = 'inline';
        }
        else {
            $disposition = 'attachment';
        }

        // start sending headers
        header('Content-type: ' . $mimetype);
        header('Content-length: ' . (string) $filesize);
        header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');
        header('Expires: ' . gmdate('D, d M Y H:i:s', mktime(date('H') + $hoursTillExpire, date('i'), date('s'), date('m'), date('d'), date('Y'))) . ' GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        // fixes for IE which give error over SSL with no-cache
        header('Cache-Control: private');  
        header('Pragma: private');

        // if data break it into an array and send
        if ($in_type == 'data') {
            // delimiter for splitting
            $delimiter = '!!**&?%$@!!';
            $file = chunk_split($in_data, $chunkLength, $delimiter);
            // reset in_data so as to not waste memory
            unset($in_data);
            $file = explode($delimiter, $file);

            foreach ($file as $line) {
                if (connection_status() != 0) {
                    return false;
                }

                echo $line;
                flush();
            }
        }
        // if file we just send it while reading it
        else {
            if ($file = fopen($in_data, 'r')) {
                while (!feof($file) && (connection_status() == 0)) {
                    echo fread($file, $chunkLength);
                    flush();
                }

                fclose($file);
            }
            else {
                return false;
            }
        }

        $status = (connection_status() == 0);

        return $status;
    }

    // }}}
    // {{{ bool    isEmpty()

    /**
     * Checks to see if a variable isset and it is not empty
     *
     * @param $in_var The variable to check
     * @access public
     * @return bool Boolean of whether it is empty or not
     */
    function isEmpty($in_var)
    {
        if ($in_var != '' && $in_var !== false && !is_null($in_var)) {
            return false;
        }
        else {
            return true;
        }
    }

    // }}}
}
?>
