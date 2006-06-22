<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006 The Codejanitor Group                        |
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
        $a_vars = array('c' => '$_COOKIE', 
                        'p' => '$_POST', 
                        'g' => '$_GET', 
                        's' => '$_SESSION', 
                        'f' => '$_FILES');
        // Set the variable in each requested type
        for ($i = 0; $i < strlen($in_type); $i++) {
            // Switch's aren't great, but variable variables of superglobals don't work in functions
            switch ($in_type{$i}) {
                case 'g':
                    $_GET[$in_varName] = $in_val;
                break;
                case 'p':
                    $_POST[$in_varName] = $in_val;
                break;
                case 's':
                    $_SESSION[$in_varName] = $in_val;
                break;
                case 'c':
                    $_COOKIE[$in_varName] = $in_val;
                break;
            }
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
        static $b_init;
        if (!isset($b_init)) {
            $b_init = true;
            if (get_magic_quotes_gpc()) {
                $_POST = FF_Request::stripslashesDeep($_POST);
                $_GET = FF_Request::stripslashesDeep($_GET);
                $_COOKIE = FF_Request::stripslashesDeep($_COOKIE);
            }
        }

        // Get the variable from the first variable type in which it is present
        for ($i = 0; $i < strlen($in_type); $i++) {
            switch($in_type{$i}) {
                case 'g':
                    if (isset($_GET[$in_varName])) {
                        return $_GET[$in_varName];
                    }
                break;
                case 'p':
                    if (isset($_POST[$in_varName])) {
                        return $_POST[$in_varName];
                    }
                break;
                case 's':
                    if (isset($_SESSION[$in_varName])) {
                        return $_SESSION[$in_varName];
                    }
                break;
                case 'c':
                    if (isset($_COOKIE[$in_varName])) {
                        return $_COOKIE[$in_varName];
                    }
                break;
                case 'f':
                    if (isset($_FILES[$in_varName])) {
                        return $_FILES[$in_varName];
                    }
                break;
            }
        }

        return $in_default;
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
    // {{{ stripslashesDeep()

    /**
     * Strips the slashes, including deep arrays
     *
     * @param  mixed $in_var The string or array to un-quote, if necessary.
     *
     * @access public 
     * @return mixed variable minus any slashes
     */
    function stripslashesDeep($in_var)
    {
        if (is_array($in_var)) {
            $in_var = array_map(array('FF_Request', 'stripslashesDeep'), $in_var);
        }
        else {
            // Stripslashes makes false turn to empty
            if (!is_bool($in_var)) {
                $in_var = stripslashes($in_var);
            }
        }

        return $in_var;
    }

    // }}}
}
?>
