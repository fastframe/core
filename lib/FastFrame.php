<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2009 The Codejanitor Group                        |
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
// | Authors: The Horde Team <http://www.horde.org>                       |
// |          Jason Rust <jrust@codejanitor.com>                          |
// |          Dan Allen <dan@mojavelinux.com>                             |
// +----------------------------------------------------------------------+

// }}}
// {{{ includes

require_once dirname(__FILE__) . '/FastFrame/Registry.php';

// }}}
// {{{ class FastFrame

/**
 * The FastFrame class provides the functionality shared by all FastFrame 
 * applications, such as creating URL's, getting a temporary file, etc.
 * This is a completely static class, all methods can be called directly.
 *
 * @version Revision: 2.0 
 * @author  The Horde Team <http://www.horde.org/>
 * @author  Jason Rust <jrust@codejanitor.com>
 * @author  Dan Allen <dan@mojavelinux.com>
 * @access  public
 * @package FastFrame
 */

// }}}
class FastFrame {
    // {{{ url()

    /**
     * Return a session-id-ified version of $uri.
     *
     * @param string $in_url The URL to be modified
     * @param array  $in_vars (optional) Set of variable = value pairs
     * @param bool   $in_full (optional) generate a full url?
     * @param bool   $in_ssl (optional) Force a ssl/https url?
     *
     * @return string The url with the session id appended
     */
    function url($in_url, $in_vars = array(), $in_full = false, $in_ssl = false)
    {
        static $s_webRoot, $b_useSSL, $s_hostname, $b_sessAppend, $s_argSep;
        // This avoids hundreds of useless function calls
        if (!isset($s_webRoot)) {
            $o_registry =& FF_Registry::singleton();
            $s_webRoot = $o_registry->getConfigParam('webserver/web_root');
            $b_useSSL = $o_registry->getConfigParam('webserver/use_ssl');
            $s_hostname = $o_registry->getConfigParam('webserver/hostname');
            $b_sessAppend = $o_registry->getConfigParam('session/append');
            $s_argSep = ini_get('arg_separator.output');
        }

        if (strpos($in_url, 'javascript:') === 0) {
            return $in_url;
        }

        // If the link does not have the webpath, then add it.
        if (strpos($in_url, '/') !== 0 && !preg_match(':^https?\://:', $in_url)) {
            $in_url = $s_webRoot . '/' . $in_url;
        }

        // See if we need to make this a full query string.
        // If using ssl then force all urls to be https.
        $b_ssl = ($in_ssl || $b_useSSL);
        if (($in_full || $b_ssl) && !preg_match(':^https?\://:', $in_url)) {
            $in_url = ($b_ssl ? 'https' : 'http') .  '://' . $s_hostname . $in_url;
        }

        // Add the session to the array list if it is configured to do so
        $s_sessName = session_name();
        if (!isset($in_vars[$s_sessName]) &&
            (!isset($_COOKIE[$s_sessName]) || $b_sessAppend)) {
            // don't lock the user out with an empty session id!
            if (!FastFrame::isEmpty(session_id())) {
                $in_vars[$s_sessName] = session_id();
            }
            elseif (!empty($_REQUEST[$s_sessName])) {
                $in_vars[$s_sessName] = urlencode($_REQUEST[$s_sessName]);
            }
        }

        // See if they want the session explicitly unset
        if (isset($in_vars[$s_sessName]) && $in_vars[$s_sessName] === false) {
            unset($in_vars[$s_sessName]);
        }

        foreach($in_vars as $k => $v) {
            $in_vars[$k] = urlencode($k) . '=' . urlencode($v);
        }

        $queryString = implode($s_argSep, $in_vars);
        if (!empty($queryString)) {
            $in_url .= (strpos($in_url, '?') === false) ? '?' : $s_argSep;
            $in_url .= $queryString;
        }

        return $in_url;
    }
    
    // }}}
    // {{{ selfURL()

    /**
     * Return a session-id-ified version of $PHP_SELF.
     *
     * @param array $in_vars (optional) Set of variable = value pairs
     * @param bool  $in_full (optional) Whether to generate a full or partial link
     * @param bool  $in_ssl (optional) Force a ssl/https url?
     *
     * @access public
     * @return string The URL
     */
    function selfURL($in_vars = array(), $in_full = false, $in_ssl = false)
    {
        // Add on the module, app, and actionId to the beginning
        $o_actionHandler =& FF_ActionHandler::singleton();
        $in_vars['app'] = isset($in_vars['app']) ? $in_vars['app'] : $o_actionHandler->getAppId();
        $in_vars['module'] = isset($in_vars['module']) ? $in_vars['module'] : $o_actionHandler->getModuleId();
        $in_vars['actionId'] = isset($in_vars['actionId']) ? $in_vars['actionId'] : $o_actionHandler->getActionId();
        // Add on isPopup if it is set
        if (FF_Request::getParam('isPopup', 'gp', false) && !isset($in_vars['isPopup'])) {
            $in_vars['isPopup'] = 1;
        }

        // Add on the url to the beginning
        return FastFrame::url($_SERVER['PHP_SELF'], $in_vars, $in_full, $in_ssl);
    }

    // }}}
    // {{{ redirect()
    
    /**
     * Given a url, send the headers to redirect to that site.
     *
     * Perform the necessary action to cause the page to reload with the
     * new url as the location.
     *
     * @param string $in_url new location
     * @param bool $in_htmlRedirect (optional) Use html to redirect the page
     *        instead of a Location header?  Useful to supress IE alerts
     *        when moving from https to http
     *
     * @access public
     * @return void
     */
    function redirect($in_url, $in_htmlRedirect = false)
    {
        if ($in_htmlRedirect) {
            echo '<html><head>
                  <script type="text/javascript">document.location.replace("' . $in_url . '"); window.onload = function() { document.body.style.display = "none"; }</script>
                  <noscript><meta http-equiv="Refresh" content="0; url=' . $in_url . '"></noscript>
                  </head><body>
                  ' . _('If you are seeing this page, your browser settings prevent you from automatically redirecting to a new URL.') . '<br /><br />
                  ' . sprintf(_('Please <a href="%s">click here</a> to continue.'), $in_url) . '
                  </body</html>';
        }
        else {
            header('Location: ' . $in_url, true);
        }

        exit;
    }

    // }}}
    // {{{ isEmpty()

    /**
     * Determine if the value of the variable is literally empty, contains no value.
     *
     * This function represents empty() in its true form.  If the variable contains absolutely
     * no data, or is just an empty array, then the boolean value 'true' is returned.  If it
     * has a value of 0 then it returns false.
     *
     * @param  mixed $in_var variable to be evaluated
     *
     * @access public
     * @return bool whether the variable has contents or not
     */
    function isEmpty($in_var)
    {
        return (empty($in_var) && strlen($in_var) == 0);
    }

    // }}}
    // {{{ checkBrowser()

    /**
     * Makes sure the user is using a supported browser.
     *
     * @access public
     * @return void Redirects user if they aren't supported.
     */
    function checkBrowser()
    {
        require_once 'Net/UserAgent/Detect.php';
        if (!IS_AJAX && Net_UserAgent_Detect::getBrowser(array('belowie6', 'belowns6', 'firefox0.x', 'belowopera8'))) {
            header('Location: notsupported.php');
            exit;
        }
    }

    // }}}
}

// {{{ PHP4 compat
if (!defined('E_STRICT')) {
    define('E_STRICT', 2048);
}

// {{{ clone()

/**
 * For cloning objects
 *
 * @param object $obj The object to clone
 *
 * @return object The cloned object
 */
if (version_compare(phpversion(), '5.0') === -1) {
    // Needs to be wrapped in eval as clone is a keyword in PHP5
    eval('
        function clone($obj)
        {
            return $obj;
        }
    ');
}

// }}} 
// }}}
?>
