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

define('FASTFRAME_AUTH_OK',        0);
define('FASTFRAME_AUTH_IDLED',     1);
define('FASTFRAME_AUTH_EXPIRED',   2);
define('FASTFRAME_AUTH_NO_LOGIN',  3);
define('FASTFRAME_AUTH_BAD_APP',   4);
define('FASTFRAME_AUTH_NO_ANCHOR', 5);
define('FASTFRAME_AUTH_LOGOUT',    6);

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
        $o_registry =& FF_Registry::singleton();
        $s_authType = $o_registry->getConfigParam('auth/method');
        $b_authenticated = false;
        $a_credentials = array('apps' => array('*'));
        $a_sources = (array) $o_registry->getConfigParam('auth/sources');
        foreach ($a_sources as $a_source) {
            $o_authSource =& FF_Auth::getAuthSourceObject($a_source['name']);
            if ($o_authSource->authenticate($in_username, $in_password)) {
                $a_credentials['authSource'] = $o_authSource->getName();
                $b_authenticated = true;
                break;
            }
        }

        if ($b_authenticated) {
            FF_Auth::setAuth($in_username, $a_credentials);
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
     * the cookie is not found they are logged out safely.  If the user
     * cannot be logged in we see if they can be logged in
     * transparently.
     *
     * @param bool $in_full Do a full check, including the idle time,
     *             exipire time, transparent methods, and session
     *             anchor?  This only needs to be done once a page load,
     *             so normally we don't do it.
     *
     * @access public
     * @return bool True on good auth, false otherwise?
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
            elseif (($a_apps = (array) FF_Auth::getCredential('apps')) &&
                    !in_array('*', $a_apps) && !in_array($o_registry->getCurrentApp(), $a_apps)) {
                FF_Auth::_setStatus(FASTFRAME_AUTH_BAD_APP);
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
            $o_registry =& FF_Registry::singleton();
            $a_sources = (array) $o_registry->getConfigParam('auth/sources');
            foreach ($a_sources as $a_source) {
                $o_authSource =& FF_Auth::getAuthSourceObject($a_source['name']);
                if ($o_authSource->hasCapability('transparent') && $o_authSource->transparent()) {
                    return true;
                }
            }

            return false;
        }

        /** 
         * If they don't have this cookie it means they either they
         * deleted it or they sent the link to someone.
         */
        if (!FF_Request::getParam(FF_Auth::_getSessionAnchor(), 'c')) {
            FF_Auth::_setStatus(FASTFRAME_AUTH_NO_ANCHOR);
            return false;
        }

        return true;
    }

    // }}}
    // {{{ isGuest()

    /**
     * Determines if the current user is in guest mode (i.e. not logged
     * in or transparently logged in)
     *
     * @access public
     * @return bool Whether or not the user is a guest
     */
    function isGuest()
    {
        return (!FF_Auth::checkAuth() || FF_Auth::getCredential('transparent'));
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
     * Sets a variable in the session indicating that authentication has
     * passed.  Extra credentials also are set.
     *
     * @param  string  $in_username The username who has been authorized
     * @param  mixed   $in_credentials (optional) other information to store about the user
     *
     * @access public
     * @return void
     */
    function setAuth($in_username, $in_credentials = array())
    {
        FF_Request::setParam('__auth__', array(
            'registered' => true,
            'status'     => FASTFRAME_AUTH_OK,
            'username'   => $in_username,
            'timestamp'  => time(),
            'idle'       => time()), 's');
        $in_credentials['username'] = $in_username;
        FF_Request::setParam('__auth__[\'credentials\']', $in_credentials, 's');
        
        // set the anchor for this browser to never expire
        $o_registry =& FF_Registry::singleton();
        FF_Request::setCookies(array(FF_Auth::_getSessionAnchor() => 1), 0,
            $o_registry->getConfigParam('cookie/path'),
            $o_registry->getConfigParam('cookie/domain'));
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
        $o_registry =& FF_Registry::singleton();
        FF_Request::unsetCookies(array('__auth__', FF_Auth::_getSessionAnchor()),
                $o_registry->getConfigParam('cookie/path'),
                $o_registry->getConfigParam('cookie/domain'));
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
     * Log the user out by clearing their session..
     *
     * @access public
     * @return object A result object with any pertinent logout messages.
     */
    function logout()
    {
        $o_result =& new FF_Result();
        $s_status = FF_Request::getParam('__auth__[\'status\']', 's');
        switch ($s_status) {
            case FASTFRAME_AUTH_IDLED:
                $o_result->addMessage(_('You have been logged out due to inactivity.'));
            break;
            case FASTFRAME_AUTH_EXPIRED:
                $o_result->addMessage(_('Your have been logged out because your session has expired.'));
            break;
            case FASTFRAME_AUTH_BAD_APP:
                $o_result->addMessage(_('You have been logged out because you have tried to access a protected application.'));
            break;
            case FASTFRAME_AUTH_NO_ANCHOR:
                $o_result->addMessage(_('Ensure that you have cookies enabled.  You have been logged out because your session anchor could not be verified.'));
            break;
            default:
                $o_result->addMessage(_('You have been successfully logged out.'));
            break;
        }

        /**
         * If logged out due to a bad session anchor then we just want
         * to redirect them to a safe logout page, but not clear the
         * session, since that would end the session for the real user.
         */
        if ($s_status != FASTFRAME_AUTH_NO_ANCHOR) {
            FF_Auth::clearAuth();
            FF_Auth::_setStatus(FASTFRAME_AUTH_LOGOUT);
            FF_Auth::_sessionEnd();
        }

        // Start session again so we don't end up with an empty session_id
        FF_Auth::sessionStart();
        return $o_result;
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
            foreach ($o_registry->getConfigParam('auth/sources') as $a_source) {
                if ($a_source['name'] == $in_name) {
                    $a_params = isset($a_source['params']) ? $a_source['params'] : array();
                    $a_authSources[$in_name] =& FF_AuthSource::factory($a_source['type'], $in_name, $a_params);
                    break;
                }
            }

            // Couldn't find an auth source, so give a dummy
            if (!isset($a_authSources[$in_name])) {
                $a_authSources[$in_name] =& new FF_AuthSource($in_name, array());
            }
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
            session_name($o_registry->getConfigParam('session/name')); 
            // Don't transparently track session ID, since we handle it.
            // Can't ini_set session.use_trans_sid, so we just empty what it searches for
            ini_set('url_rewriter.tags', '');
            // set the cacheing 
            session_cache_limiter($o_registry->getConfigParam('session/cache', 'nocache'));
            $isStarted = true;
        }

        @session_start();
    }

    // }}}
    // {{{ encryptPassword()

    /**
     * Encrypts a password with one of the encryption methods
     *
     * @param string $in_plain The plaintext password
     * @param string $in_method The encryption method
     *
     * @access public
     * @return string An encrypted password
     */
    function encryptPassword($in_plain, $in_method)
    {
        switch ($in_method) {
            case 'md5':
                return md5($in_plain);
            break;
            case 'plain':
            default:
                return $in_plain;
            break;
        }

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
