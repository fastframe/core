<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2003 The Codejanitor Group                        |
// +----------------------------------------------------------------------+
// | This source file is subject to the GNU Lesser Public License (LGPL), |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.fsf.org/copyleft/lesser.html                              |
// | If you did not receive a copy of the LGPL and are unable to          |
// | obtain it through the world-wide-web, you can get it by writing the  |
// | Free Software Foundation, Inc., 59 Temple Place - Suite 330, Boston, |
// | MA 02111-1307, USA.                                                  |
// +----------------------------------------------------------------------+
// | Authors: Jason Rust <jrust@codejanitor.com>                          |
// +----------------------------------------------------------------------+

// }}}
// {{{ class FF_Request

/**
 * The FF_Request:: class allows for easy getting and setting of the PHP
 * EGPCS data: $_SESSION, $_GET, $_POST, $_COOKIE, $_FILES.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package FastFrame
 */

// }}}
class FF_Request {
    // {{{ setParam()

    /**
     * Sets the the request variable.  Allows for setting multiple
     * request variables at one time.
     *
     * @param  string $in_varName The name of the variable.
     * @param  mixed $in_val The value to set.
     * @param  string $in_type The type of variable (g (get), p
     *         (post), c (cookie), s (session), f (file)).
     *
     * @access public
     * @return void
     */
    function setParam($in_varName, $in_val, $in_type)
    {
        $tmp_len = strlen($in_type);
        $tmp_vars = FF_Request::_getRequestVars();
        $s_varName = FF_Request::_getVarName($in_varName);
        // Set the variable in each requested type
        for ($i = 0; $i < $tmp_len; $i++) {
            $tmp_var = $tmp_vars[$in_type{$i}] . $s_varName;
            eval($tmp_var . ' = $in_val;');
        }
    }

    // }}}
    // {{{ getParam()

    /**
     * Get the the request variable.
     *
     * Since servers have gpc_magic_quotes enabled, this is an easy way
     * to strip them off when we grab the data.  Additionally, it
     * allows us to check multiple request variables in the order
     * specified, and return a default value if the requested variable
     * isn't found.
     *
     * @param  string $in_varName The name of the variable.
     * @param  string $in_type (optional) The type of variable (g (get),
     *         p (post), c (cookie), s (session), f (file)).  Default is
     *         'gpc'
     * @param  string $in_default (optional) The default value if no
     *         value is found
     *
     * @access public
     * @return mixed Value of the variable or default value
     */
    function getParam($in_varName, $in_type = 'gpc', $in_default = null)
    {
        $tmp_len = strlen($in_type);
        $tmp_vars = FF_Request::_getRequestVars();
        $s_varName = FF_Request::_getVarName($in_varName);
        // Get the variable from the first variable type in which it is present
        for ($i = 0; $i < $tmp_len; $i++) {
            $tmp_var = $tmp_vars[$in_type{$i}] . $s_varName;
            if (eval('return isset(' . $tmp_var . ');')) {
                $s_data = eval('return ' . $tmp_var . ';');
                break;
            }
        }

        if (isset($s_data)) {
            return FF_Request::_disableMagicQuotes($s_data);
        }
        else {
            return $in_default;
        }
    }

    // }}}
    // {{{ unsetCookies()

    /**
     * Clear the values of a cookie list
     *
     * Since there is no clean way to do this in php, we provide this
     * function to force set the cookie to a zero value and in the past.
     *
     * @param  mixed  $in_names Name of the cookie or array of string names
     *
     * @access public
     * @return void
     */
    function unsetCookies($in_names, $in_path = '/', $in_domain = '') 
    {
        foreach ((array) $in_names as $s_name) {
            setcookie($s_name, '', time() - 3600, $in_path, $in_domain); 
            FF_Request::setParam($s_name, null, 'c'); 
        }
    }

    // }}}
    // {{{ setCookies()

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
        foreach((array) $in_nameValues as $s_name => $s_value) {
            setcookie($s_name, $s_value, $in_expire, $in_path, $in_domain); 
            FF_Request::setParam($s_name, $s_value, 'c'); 
        }
    }

    // }}}
    // {{{ _disableMagicQuotes()

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
    function _disableMagicQuotes(&$in_var)
    {
        static $b_magicQuotesEnabled;

        if (!isset($b_magicQuotesEnabled)) {
            $b_magicQuotesEnabled = get_magic_quotes_gpc();
        }

        if ($b_magicQuotesEnabled) {
            if (is_array($in_var)) {
                array_walk($in_var, array('FF_Request', '_disableMagicQuotes'));
            }
            else {
                // Stripslashes makes false turn to empty
                if (!is_bool($in_var)) {
                    $in_var = stripslashes($in_var);
                }
            }
        }

        return $in_var;
    }

    // }}}
    // {{{ _getRequestVars()

    /**
     * Gets an array of the request variables
     *
     * @access private
     * @return array An array of the request variables (key is the
     *         abbreviation for the var)
     */
    function _getRequestVars()
    {
        return $requestVars = array(
            'c' => '$_COOKIE', 
            'p' => '$_POST', 
            'g' => '$_GET', 
            's' => '$_SESSION', 
            'f' => '$_FILES');
    }

    // }}}
    // {{{ _getVarName()

    /**
     * Gets the appropriate variable name for accessing an element from
     * one of the request arrays.  Supports nested array variables.
     *
     * @param string $in_varName The name of the variable.
     *
     * @access private
     * @return string The new variable name.
     */
    function _getVarName($in_varName)
    {
        // Can't have single quotes in variable since we put them in if needed
        $s_varName = str_replace('\'', '', $in_varName);

        // If the variable is an array then create the correct key (i.e. foo['bar'])
        if ($tmp_pos = strpos($s_varName, '[')) {
            $s_varName = '[\'' . substr($s_varName, 0, $tmp_pos) . '\']' . 
                str_replace(array('[', ']'), array('[\'', '\']'), substr($s_varName, $tmp_pos));
        }
        else {
            $s_varName = '[\'' . $s_varName . '\']';
        }

        return $s_varName;
    }

    // }}}
}
?>
