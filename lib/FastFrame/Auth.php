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
// | Authors: The Horde Team <http://www.horde.org>                       |
// |          Jason Rust <jrust@codejanitor.com>                          |
// |          Dan Allen <dan@mojavelinux.com>                             |
// +----------------------------------------------------------------------+

// }}}
// {{{ constants

define('FASTFRAME_AUTH_OK',         0);
define('FASTFRAME_AUTH_IDLED',     -1);
define('FASTFRAME_AUTH_EXPIRED',   -2);
define('FASTFRAME_AUTH_NO_LOGIN',  -3);
define('FASTFRAME_AUTH_BAD_LOGIN', -4);
define('FASTFRAME_AUTH_LOGOUT',    -5);

// }}}
// {{{ includes

require_once dirname(__FILE__) . '/Registry.php';

// }}}
// {{{ class FF_Auth

/**
 * An authentication class for the FastFrame framework.
 *
 * This class works in the following way.  If auth passes, then we set a single cookie that
 * will be the key to the sessionID in the query string.  This way, we will link a browser
 * to this session.  We don't want to use sessions in cookies because then we can only have
 * one session for this application per browser, whereas in the query string multiple are
 * possible.  But we still want this browser to be responsible for this session.
 *
 * @version Revision: 2.0 
 * @author  The Horde Team <http://www.horde.org/>
 * @author  Jason Rust <jrust@codejanitor.com>
 * @author  Dan Allen <dan@mojavelinux.com>
 * @access  public
 * @package FastFrame
 */

// }}}
class FF_Auth {
    // {{{ authenticate()

    /**
     * Perform the login procedure.
     *
     * @param string $in_username The username to be authenticated.
     * @param string $in_password The corresponding password.
     *
     * @access public
     * @return bool determines if login was successfull
     */
    function authenticate($in_username, $in_password) 
    {
        // if we are already logged in, just return true immediately
        if (FF_Auth::checkAuth(false)) {
            return true;
        }

        $o_registry =& FF_Registry::singleton();
        $s_authType = $o_registry->getConfigParam('auth/method');
        $b_authenticated = false;
        $a_credentials = array();
        // populate all of the authentication sources
        $a_sources = $o_registry->getConfigParam('auth/sources');
        if (is_array($a_sources)) {
            foreach ($a_sources as $s_name => $a_source) {
                // Get an AuthSource of the proper type
                $o_authSource =& FF_Auth::getAuthSourceObject($s_name);
                if ($o_authSource->authenticate($in_username, $in_password)) {
                    $a_credentials['authSource'] = $o_authSource->getName();
                    $b_authenticated = true;
                    break;
                }
            }
        }
        else {
            return FastFrame::fatal('No authentication source was defined in the config file.', __FILE__, __LINE__);
        }

        if ($b_authenticated) {
            FF_Auth::setAuth($in_username, $a_credentials, true);
            return true;
        }
        else {
            return false;
        }
    }

    // }}}
    // {{{ checkAuth()

    /**
     * Check whether the user is authenticated.
     *
     * Checks if there is a session with valid the valid authentication
     * information. This includes checking for the session cookie anchor
     * to make sure this session did not leak to another browser.
     *
     * @param  bool  $in_checkAnchor (optional) check for session cookie anchor...we would
     *               turn this off if we are just checking to see if we ever logged in before
     *
     * @access public
     * @return bool {or void logout exception}
     */
    function checkAuth($in_checkAnchor = true)
    {
        if (isset($_SESSION['__auth__'])) {
            $o_registry =& FF_Registry::singleton();
            if (isset($_SESSION['__auth__']['timestamp']) &&
                ($expire = $o_registry->getConfigParam('session/expire')) > 0 && 
                ($_SESSION['__auth__']['timestamp'] + $expire) < time()) {
                FF_Auth::_set_status(FASTFRAME_AUTH_EXPIRED);
                FF_Auth::_update_idle();
                return false;
            }
            elseif (isset($_SESSION['__auth__']['idle']) &&
                    ($idle = $o_registry->getConfigParam('session/idle')) > 0 && 
                    ($_SESSION['__auth__']['idle'] + $idle) < time()) {
                FF_Auth::_set_status(FASTFRAME_AUTH_IDLED);
                return false;
            }
            elseif (!empty($_SESSION['__auth__']['registered'])) {
                FF_Auth::_set_status(FASTFRAME_AUTH_OK);
                FF_Auth::_update_idle();
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }

        /** 
         * If they don't have this cookie it means they either they deleted it or they sent
         * the link to someone. In either case we just want to redirect them to a safe
         * logout page, but not clear the session, since that would end the session for the
         * real user.
         */
        if ($in_checkAnchor && !FastFrame::getCGIParam(FF_Auth::_get_session_anchor(), 'c')) {
            FF_Auth::_safe_logout();
            return false;
        }

        return true;
    }

    // }}}
    // {{{ getIdle()

    /**
     * Return the current idle time 
     *
     * @access public
     * @return int The idle timestamp  
     */
    function getIdle()
    {
        return $_SESSION['__auth__']['idle'];
    }

    // }}}
    // {{{ setAuth()

    /**
     * Force a transparent login.
     *
     * Set the appropriate variables so as to force a transparent login using
     * the username.  The credentials are the extra information on the user such
     * as the username
     *
     * @param  string  $in_username The username who has been authorized
     * @param  mixed   $in_credentials (optional) other information to store about the user
     * @param  bool    $in_setAnchor (optional) Set the anchor to tie this session to the browser?
     *
     * @access public
     * @return void
     */
    function setAuth($in_username, $in_credentials = null, $in_setAnchor = false)
    {
        $_SESSION['__auth__'] = array(
            'registered' => true,
            'status'     => FASTFRAME_AUTH_OK,
            'username'   => $in_username,
            'timestamp'  => time(),
            'idle'       => time(),
        );
        
        if (!is_null($in_credentials)) {
            $_SESSION['__auth__']['credentials'] = $in_credentials;
        }

        // username is a credential as well as an auth item
        $_SESSION['__auth__']['credentials']['username'] = $in_username;
        
        // set the anchor for this browser to never expire
        if ($in_setAnchor) {
            FastFrame::setCookies(array(FF_Auth::_get_session_anchor() => 1));
        }
    }

    // }}}
    // {{{ clearAuth()

    /**
     * Clear any authentication tokens in the current session.
     *
     * @access public
     * @return void
     */
    function clearAuth()
    {
        // kill the anchor and the __auth__ cookie 
        FastFrame::unsetCookies(array('__auth__', FF_Auth::_get_session_anchor()));

        $_SESSION['__auth__'] = array();
        $_SESSION['__auth__']['registered'] = false;
    }

    // }}}
    // {{{ getStatus()

    /**
     * Get the current status of the authentication.
     *
     * The status will allow one to follow where we are in the login process
     * and report appropriate error messages based on this status.
     *
     * @access public
     * @return int auth status code
     */
    function getStatus()
    {
        if (isset($_SESSION['__auth__']['status'])) {
            return $_SESSION['__auth__']['status'];
        }
        else {
            return FASTFRAME_AUTH_NO_LOGIN;
        }
    }

    // }}}
    // {{{ setCredential()

    /**
     * Set an individual credential
     *
     * @param string $in_credential The credential name
     * @param string $in_value The credential value
     *
     * @access public
     * @return void
     */
    function setCredential($in_credential, $in_value)
    {
        $_SESSION['__auth__']['credentials'][$in_credential] = $in_value;
    }

    // }}}
    // {{{ getCredential()

    /**
     * Get a credential from the stack of user properties
     *
     * Return the credential of the user such as the username 
     *
     * @access public
     * @return mixed credential value if exists or false if not logged in or no such credential
     */
    function getCredential($in_credential)
    {
        if (!empty($_SESSION['__auth__']['credentials'])) {
            $credentials = $_SESSION['__auth__']['credentials'];
            if (isset($credentials[$in_credential])) {
                return $credentials[$in_credential];
            }
        }
 
        return null;
    }

    // }}}
    // {{{ logout()

    /**
     * Log the user out and unset their authenticated status.
     *
     * @param string $in_logoutURL (optional) The logout url.  Otherwise we determine it
     * @param bool $in_return (optional) Just return instead of redirecting to logout page?
     *
     * @access public
     * @return mixed Redirect on success, false on failure.
     */
    function logout($in_logoutURL = null, $in_return = false) 
    {
        if (is_null($in_logoutURL) && !$in_return) {
            $o_registry =& FF_Registry::singleton();
            $s_logoutApp = $o_registry->getConfigParam('general/logout_app', $o_registry->getConfigParam('general/login_app'));
            $s_logoutURL = FastFrame::url($o_registry->getRootFile('index.php', null, FASTFRAME_WEBPATH), array('app' => $s_logoutApp), true);
        }
        else {
            $s_logoutURL = $in_logoutURL;
        }

        FF_Auth::clearAuth();
        FF_Auth::_set_status(FASTFRAME_AUTH_LOGOUT);
        FF_Auth::_session_end();

        if ($in_return) {
            return true;
        }
        else {
            FastFrame::redirect($s_logoutURL);
        }
    }

    // }}}
    // {{{ getAuthSourceObject()

    /**
     * Returns the appropriate auth source object
     *
     * @param string $in_name The name of the auth source to get
     *
     * @access public
     * @return object An auth source object
     */
    function &getAuthSourceObject($in_name)
    {
        static $a_authSources;
        if (!isset($a_authSources)) {
            $a_authSources = array();
            require_once dirname(__FILE__) . '/Auth/AuthSource.php';
        }

        if (!isset($a_authSources[$in_name])) {
            $o_registry =& FF_Registry::singleton();
            $a_authSources[$in_name] =& FF_AuthSource::factory(
                                            $o_registry->getConfigParam("auth/sources/$in_name/type"), 
                                            $in_name, 
                                            $o_registry->getConfigParam("auth/sources/$in_name/params", array())
                                        );
        }

        return $a_authSources[$in_name];
    }

    // }}}
    // {{{ sessionStart()

    /**
     * Start a session.
     *
     * This function determines if a session has been started and if not, starts
     * the session, using the value from the registry for the name of the session.
     * It also disables the cookie params for sessions since they suck.
     *
     * @access public 
     * @return void
     */
    function sessionStart() {
        static $isStarted;
        
        if (!isset($isStarted)) {
            $o_registry =& FF_Registry::singleton();
            // Don't use cookies to do the session if it will be appended to the URL
            if ($o_registry->getConfigParam('session/append')) {
                ini_alter('session.use_cookies', 0);
            }
            else {
                ini_alter('session.use_cookies', 1);
                session_set_cookie_params(0, $o_registry->getConfigParam('webserver/web_root'),
                        $o_registry->getConfigParam('webserver/hostname'));
            }

            // Use a common session name for all apps
            session_name(urlencode($o_registry->getConfigParam('session/name'))); 
            // Don't transparently track session ID, since we handle it.
            ini_set('session.use_trans_sid', 0);
            // set the cacheing 
            session_cache_limiter($o_registry->getConfigParam('session/cache', 'nocache'));

            // get the session name from the configuration or just use a default
            session_name($o_registry->getConfigParam('session/name', 'FF_SESSID'));
            session_start();
            $isStarted = true;
        }
    }

    // }}}
    // {{{ _session_end()

    /**
     * End a session.
     *
     * @access private
     * @return bool
     */
    function _session_end() 
    {
        @session_destroy();
    }

    // }}}
    // {{{ _get_session_anchor()

    /**
     * Generate a unique key that will link the session to the browser.
     *
     * We don't want people to simply pass around links, but at the same time
     * cookie-based sessionIDs suck because you cannot have multiple sessions
     * Why? ...because you get stuck in the 'Which comes first?' debate.
     * So, it is absolutely necessary to use the query string for the sessionID
     * but then we need to anchor that session ID to this browser.
     *
     * @access private
     * @return string unique cookie name to be set for this browser
     */
    function _get_session_anchor()
    {
        return @md5($_SESSION['__auth__']['username']);
    }

    // }}}
    // {{{ _set_status()

    /**
     * Set the status on the authentication.
     *
     * @access private
     * @return void
     */
    function _set_status($in_status)
    {
        $_SESSION['__auth__']['status'] = $in_status;
    }

    // }}}
    // {{{ _update_idle()

    function _update_idle() 
    {
        // update the timestamp for the idle time
        $_SESSION['__auth__']['idle'] = time();
    }

    // }}}
    // {{{ _safe_logout()

    /**
     * Safetly deny access to a user without the anchor variable set
     *
     * Send the user to the logout page, but don't clear the
     * session and authentication variables.  This is used in
     * the case that the url was given to someone else...if we 
     * perform a true logout we destroy the session of the user
     * who is already logged in.
     *
     * @access private
     * @return void 
     */
    function _safe_logout() 
    {
        $registry =& FF_Registry::singleton();
        FastFrame::redirect($registry->getConfigParam('session/safelogout'));
    }

    // }}}
}
?>
