<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2004 The Codejanitor Group                        |
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
        if (FF_Auth::checkAuth()) {
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
            trigger_error('No authentication source was defined in the config file.', E_USER_ERROR);
            return false;
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
     * to make sure this session did not leak to another browser.  If
     * the cookie is not found they are logged out safely.
     *
     * @param bool $in_full Do a full check, including the idle time,
     *             exipire time, and session anchor?  This only needs to
     *             be done once a page load, so normally we don't do it.
     *
     * @access public
     * @return bool {or void logout exception}
     */
    function checkAuth($in_full = false)
    {
        if (!$in_full) {
            return FF_Request::getParam('__auth__[\'registered\']', 's', false);
        }

        if (FF_Request::getParam('__auth__', 's', false) !== false) {
            $o_registry =& FF_Registry::singleton();
            if (FF_Request::getParam('__auth__[\'timestamp\']', 's', false) &&
                ($expire = $o_registry->getConfigParam('session/expire')) > 0 && 
                (FF_Request::getParam('__auth__[\'timestamp\']', 's') + $expire) < time()) {
                FF_Auth::_setStatus(FASTFRAME_AUTH_EXPIRED);
                FF_Auth::_updateIdle();
                return false;
            }
            elseif (FF_Request::getParam('__auth__[\'idle\']', 's', false) &&
                    ($idle = $o_registry->getConfigParam('session/idle')) > 0 && 
                    (FF_Request::getParam('__auth__[\'idle\']', 's') + $idle) < time()) {
                FF_Auth::_setStatus(FASTFRAME_AUTH_IDLED);
                return false;
            }
            elseif (FF_Request::getParam('__auth__[\'registered\']', 's', false)) {
                FF_Auth::_setStatus(FASTFRAME_AUTH_OK);
                FF_Auth::_updateIdle();
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }

        /** 
         * If they don't have this cookie it means they either they
         * deleted it or they sent the link to someone. In either case
         * we just want to redirect them to a safe logout page, but not
         * clear the session, since that would end the session for the
         * real user.
         */
        if (!FF_Request::getParam(FF_Auth::_getSessionAnchor(), 'c')) {
            FF_Auth::logout(null, false, true);
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
        return FF_Request::getParam('__auth__[\'idle\']', 's');
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
        FF_Request::setParam('__auth__', array(
            'registered' => true,
            'status'     => FASTFRAME_AUTH_OK,
            'username'   => $in_username,
            'timestamp'  => time(),
            'idle'       => time()), 's');
        
        if (!is_null($in_credentials)) {
            FF_Request::setParam('__auth__[\'credentials\']', $in_credentials, 's');
        }

        // username is a credential as well as an auth item
        FF_Request::setParam('__auth__[\'credentials\'][\'username\']', $in_username, 's');
        
        // set the anchor for this browser to never expire
        if ($in_setAnchor) {
            FF_Request::setCookies(array(FF_Auth::_getSessionAnchor() => 1), 0,
                $this->o_registry->getConfigParam('cookie/path'),
                $this->o_registry->getConfigParam('cookie/domain'));
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
        FF_Request::unsetCookies(array('__auth__', FF_Auth::_getSessionAnchor()),
                $this->o_registry->getConfigParam('cookie/path'),
                $this->o_registry->getConfigParam('cookie/domain'));
        FF_Request::setParam('__auth__', array(), 's');
        FF_Request::setParam('__auth__[\'registered\']', false, 's');
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
        return FF_Request::getParam('__auth__[\'status\']', 's', FASTFRAME_AUTH_NO_LOGIN);
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
        FF_Request::setParam('__auth__[\'credentials\'][\'' . $in_credential . '\']', $in_value, 's');
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
        return FF_Request::getParam('__auth__[\'credentials\'][\'' . $in_credential . '\']', 's');
    }

    // }}}
    // {{{ logout()

    /**
     * Log the user out.
     *
     * @param string $in_logoutURL (optional) The logout url.  Otherwise we determine it
     * @param bool $in_return (optional) Just return instead of redirecting to logout page?
     * @param bool $in_safe (optional) Do a safe logout, where the session is not cleared?
     *             Sends the user to the logout page, but doesn't clear the session and
     *             authentication variables.  This is used in the case that the url was
     *             given to someone else...if we perform a true logout we destroy the
     *             session of the user who is already logged in.
     *
     * @access public
     * @return mixed Redirect on success, true if $in_return is set
     */
    function logout($in_logoutURL = null, $in_return = false, $in_safe = false) 
    {
        if (is_null($in_logoutURL) && !$in_return) {
            $o_registry =& FF_Registry::singleton();
            $s_logoutApp = $o_registry->getConfigParam('general/logout_app');
            // Get the request url without the session.  Not using selfURL() because it calls
            // action handler which can make an infinite loop.
            $s_redirectURL = preg_replace('/[?&]' . session_name() . '=[^?&]*/', '', $_SERVER['REQUEST_URI']);
            $s_logoutURL = FastFrame::url(
                    $o_registry->getRootFile('index.php', null, FASTFRAME_WEBPATH), 
                    array('app' => $s_logoutApp, 'loginRedirect' => $s_redirectURL, session_name() => false), true);
        }
        else {
            $s_logoutURL = $in_logoutURL;
        }

        if (!$in_safe) {
            FF_Auth::clearAuth();
            FF_Auth::_setStatus(FASTFRAME_AUTH_LOGOUT);
            FF_Auth::_sessionEnd();
        }

        if ($in_return) {
            // Start session again so we don't end up with an empty session_id
            FF_Auth::sessionStart();
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
    function sessionStart() 
    {
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
            $isStarted = true;
        }

        @session_start();
    }

    // }}}
    // {{{ _sessionEnd()

    /**
     * End a session.
     *
     * @access private
     * @return bool
     */
    function _sessionEnd() 
    {
        @session_destroy();
    }

    // }}}
    // {{{ _getSessionAnchor()

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
    function _getSessionAnchor()
    {
        return @md5(FF_Request::getParam('__auth__[\'username\']', 's'));
    }

    // }}}
    // {{{ _setStatus()

    /**
     * Set the status on the authentication.
     *
     * @access private
     * @return void
     */
    function _setStatus($in_status)
    {
        FF_Request::setParam('__auth__[\'status\']', $in_status, 's');
    }

    // }}}
    // {{{ _updateIdle()

    function _updateIdle() 
    {
        // update the timestamp for the idle time
        FF_Request::setParam('__auth__[\'idle\']', time(), 's');
    }

    // }}}
}
?>
