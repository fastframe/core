<?php
/** $Id: Auth.php,v 1.2 2003/01/15 18:25:02 jrust Exp $ */
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
// {{{ class FastFrame_Auth

/**
 * An authentication class for the FastFrame framework.
 *
 * This class works in the following way.  If auth passes, then we set a single cookie that
 * will be the key to the sessionID in the query string.  This way, we will link a browser
 * to this session.  We don't want to use sessions in cookies because then we can only have
 * one session for this application per browser, whereas in the query string multiple are
 * possible.  But we still want this browser to be responsible for this session.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lesser.html.
 *
 * @version Revision: 2.0 
 * @author  Horde  http://www.horde.org/
 * @author  Jason Rust <jrust@rustyparts.com>
 * @author  Dan Allen <dan@mojavelinux.com>
 * @access  public
 * @package FastFrame
 */

// }}}
class FastFrame_Auth {
    // {{{ singleton()

    /**
     * To be used in place of the contructor to return any open instance.
     *
     * The idea with a registry is that we only ever want a single instance since
     * all of the properties and methods must map to a single state.  Therefore, this
     * function is used in place of the contructor to return any open instances of
     * the auth object, and if none are open will create a new instance and cache
     * it using a static variable.
     *
     * @access public
     * @return object FastFrame_Auth instance
     */
    function &singleton()
    {
        static $instance;

        if (!isset($instance)) {
            $instance =& new FastFrame_Auth();
        }

        return $instance; 
    }

    // }}}
    // {{{ constructor
    
    /**
     * Initialize the FastFrame_Auth class
     *
     * Create an instance of the FastFrame_Auth class.  When we call the constructor
     * or the singleton() function which in turn calls the constructor, we want
     * to start the session.  There the session name will be registered and the
     * session started up.
     *
     * @access public
     * @return FastFrame_Auth object
     */
    function FastFrame_Auth()
    {
        FastFrame_Auth::_session_start();
    }

    // }}}
    // {{{ authenticate()

    /**
     * Perform the login procedure.
     *
     * @access public
     * @return boolean determines if login was successfull
     */
    function authenticate() 
    {
        // if we are already logged in, just return true immediately
        if (FastFrame_Auth::checkAuth(false)) {
            return true;
        }

        $o_registry =& FastFrame_Registry::singleton();
        $o_registry->pushApp('login');
        $s_authType = $o_registry->getConfigParam('auth/method');
        $o_registry->popCurrentApp();
        $s_username = FastFrame::getCGIParam('form_username', 'p');
        $s_password = FastFrame::getCGIParam('form_password', 'p');
        $b_authenticated = false;

        if ($s_authType == 'guest') {
            $b_authenticated = true;
            $a_credentials = array(
                'perms' => 0,
            );
        }
        elseif ($s_authType == 'database') {
            if ($s_username == 'jrust' && $s_password == 'foo') {
                $b_authenticated = true;
                $a_credentials = array(
                    'perms' => 2,
                );
            }
        }

        if ($b_authenticated) {
            FastFrame_Auth::setAuth($s_username, $a_credentials, true);
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
     * @return boolean {or void logout exception}
     */
    function checkAuth($in_checkAnchor = true)
    {
        if (isset($_SESSION['__auth__'])) {
            $o_registry =& FastFrame_Registry::singleton();
            if (($expire = $o_registry->getConfigParam('session/expire')) > 0 && 
                ($_SESSION['__auth__']['timestamp'] + $expire) < time()) {
                FastFrame_Auth::_set_status(FASTFRAME_AUTH_EXPIRED);
                FastFrame_Auth::_update_idle();
                return false;
            }
            elseif (($idle = $o_registry->getConfigParam('session/idle')) > 0 && 
                    ($_SESSION['__auth__']['idle'] + $idle) < time()) {
                FastFrame_Auth::_set_status(FASTFRAME_AUTH_IDLED);
                return false;
            }
            elseif (!empty($_SESSION['__auth__']['registered'])) {
                FastFrame_Auth::_set_status(FASTFRAME_AUTH_OK);
                FastFrame_Auth::_update_idle();
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
        if ($in_checkAnchor && !FastFrame::getCGIParam(FastFrame_Auth::_get_session_anchor(), 'c')) {
            FastFrame_Auth::_safe_logout();
            return false;
        }

        return true;
    }

    // }}}
    // {{{ getAuth()

    /**
     * Return the currently logged in user, if there is one.
     *
     * Check to see if there is a valid user by determinig if the registered bit
     * is set on the __auth__ array and if so return that userID, else return false
     *
     * @access public
     * @return mixed either the userID of the current user or false if there is no registered user
     */
    function getAuth()
    {
        if (isset($_SESSION['__auth__']) && 
            (boolean) $_SESSION['__auth__']['registered'] == true && 
            !@isempty($_SESSION['__auth__']['userID'])) {
            return $_SESSION['__auth__']['userID'];
        }

        return false;
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
     * the userID.  The credentials are the extra information on the user such
     * as the companyID and the perms of the user
     *
     * @param  string  $in_userID The userID who has been authorized
     * @param  mixed   $in_credentials (optional) other information to store about the user
     * @param  bool    $in_setAnchor Set the anchor to tie this session to the browser?
     *
     * @access public
     * @return void
     */
    function setAuth($in_userID, $in_credentials = null, $in_setAnchor = false)
    {
        $_SESSION['__auth__'] = array(
            'registered' => true,
            'status'     => FASTFRAME_AUTH_OK,
            'userID'     => $in_userID,
            'timestamp'  => time(),
            'idle'       => time()
        );
        
        if (!is_null($in_credentials)) {
            $_SESSION['__auth__']['credentials'] = $in_credentials;
        }
        
        // set the anchor for this browser to never expire
        if ($in_setAnchor) {
            FastFrame::setCookies(array(FastFrame_Auth::_get_session_anchor() => 1));
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
        FastFrame::unsetCookies(array('__auth__', FastFrame_Auth::_get_session_anchor()));

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
        if (FastFrame_Auth::checkAuth(false)) {
            $_SESSION['__auth__']['credentials'][$in_credential] = $in_value;
        }
    }

    // }}}
    // {{{ getCredential()

    /**
     * Get a credential from the stack of user properties
     *
     * Return the credential of the user such as the companyID or perms 
     *
     * @access public
     * @return mixed credential value if exists or false if not logged in or no such credential
     */
    function getCredential($in_credential)
    {
        if (FastFrame_Auth::checkAuth(false)) {
            $credentials = $_SESSION['__auth__']['credentials'];
            if (isset($credentials[$in_credential])) {
                return $credentials[$in_credential];
            }
        }
 
        return null;
    }

    // }}}
    // {{{ createCredential()

    /**
     * Creates a username or password, either randomly
     * or based on the field in the companies table 
     *
     * @param string $in_type Either 'password' or 'username'
     * @param bool $in_random (optional) Force this to be random?
     * @param string $in_userID (optional) The user ID, otherwise we use current userID
     * @param string $in_domain (optional) The domain (employee or admin)
     * @param string $in_table (optional) The table to pull data from if the company has
     *               has a special rule.  Normally we use current_main or admin 
     *
     * @access public
     * @return string The username
     */
    function createCredential($in_type, $in_random = false, $in_userID = null, $in_domain = 'employee', $in_table = null)
    {
        $registry =& FastFrame_Registry::singleton();
        $info =& FastFrame_Info::singleton();
        $db = $registry->getDataConnection();
        $fields =& FastFrame_Central::factory('Fields');

        if (is_null($in_userID)) {
            $userID = $info->getUserID();
        }
        else {
            $userID = $in_userID;
        }

        $random = $in_random;
        $rule = '';
        // get their rule (if it's not random)
        if ($in_type == 'password' && !$random) {
            $rule = $info->getCompanyParam('passwordRule');
        }
        elseif ($in_type == 'username' && !$random) {
            $rule = $info->getCompanyParam('usernameRule');
        }

        if ($rule == '') {
            $random = true;
        }

        if ($random) {
            // exclude I, O, 0, and 1 (too much alike)
            $possibleChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ23456789';
            $isUnique = false;
            while (!$isUnique) {
                $credential = FastFrame::getRandomString(8, $possibleChars);
                if ($in_type == 'username') {
                    $isUnique = FastFrame_Auth::_is_unique_username($credential);
                }
                else {
                    $isUnique = true;
                }
            }
        }
        else {
            // check for central field first 
            if (($tmp_myname = $fields->isFieldID($rule)) !== false) {
                $credential = $fields->getFieldForUser($tmp_myname, false, false, $userID);
                // if error just get random
                if (FastFrame_Central::isError($credential)) {
                    $credential = FastFrame_Auth::createCredential($in_type, true);
                }
            }
            else {
                // get the credential using a regular field
                // determine table to pull data from
                if (is_null($in_table)) {
                    if ($in_domain == 'employee') {
                        $table = 'current_main';
                    }
                    else {
                        $table = 'admin';
                    }
                }
                else {
                    $table = $in_table;
                }

                $query = '
                SELECT ' . $rule . ' FROM `' . $registry->getCompanyDatabase() . '`.`' . $table . '` 
                WHERE `f_ssn`=' . $db->quote($userID);

                $credential = $db->getOne($query);
                // if error just get random
                if (DB::isError($credential)) {
                    $credential = FastFrame_Auth::createCredential($in_type, true);
                }
            }

            if ($in_type == 'username') {
                // make sure it is a unique username
                if (!FastFrame_Auth::_is_unique_username($credential, $in_table)) {
                    // if not just create a random one
                    $credential = FastFrame_Auth::createCredential('username', true);
                }
            }
        }

        return $credential;
    }

    // }}}
    // {{{ decryptPassword()

    /**
     * Decrypts the user's password, right now using our 
     * own homegrown system, but someday this may be something better
     *
     * @param string $in_pass The password to decrypt
     *
     * @access public 
     * @return string The unencrypted password
     */
    function decryptPassword($in_pass)
    {
        if (($length = strlen($in_pass)) < 4) {
            return $in_pass;
        }

        // encryption mask
        $mask = array(2, -1, 1, -2, -1, 1, 1);
        $maskLength = count($mask);

        $newString = '';
        for ($i = 0; $i < $length; $i++) {
            if ($i % 2 == 1) {
                // for every other character get the character minus the mask
                $newString .= chr(ord($in_pass[$i]) - $mask[(($i + 1) % $maskLength)]);
            }
        }

        return strrev($newString);
    }

    // }}}
    // {{{ encryptPassword()

    /**
     * Encrypts the user's password, right now using our 
     * own homegrown system, but someday this may be something better
     *
     * @param string $in_text The text to encrypt 
     *
     * @access public 
     * @return string The encrypted password
     */
    function encryptPassword($in_text)
    {
        if (($length = strlen($in_text)) <= 2) {
            return $in_text;
        }
        $text = strrev($in_text);

        // encryption mask
        $mask = array(2, -1, 1, -2, -1, 1, 1);
        $maskLength = count($mask);

        $pass = '';
        // note: string is doubled in size
        for ($i = 0; $i < $length; $i++) {
            // add random letter
            $pass .= chr(mt_rand(63, 126));
            // change the ascii value of each char according to mask 
            $pass .= chr(ord($text[$i]) + $mask[((($i + 1) * 2) % $maskLength)]);
        }

        return $pass;
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
            $o_registry = FastFrame_Registry::singleton();
            $s_logoutApp = $o_registry->getConfigParam('general/logout_app', $o_registry->getConfigParam('general/login_app'));
            $s_logoutURL = FastFrame::url($o_registry->getAppFile('index.php', $s_logoutApp, '', FASTFRAME_WEBPATH), true);
        }
        else {
            $s_logoutURL = $in_logoutURL;
        }

        FastFrame_Auth::clearAuth();
        FastFrame_Auth::_set_status(FASTFRAME_AUTH_LOGOUT);
        FastFrame_Auth::_session_end();

        if ($in_return) {
            return true;
        }
        else {
            FastFrame::redirect($s_logoutURL);
        }
    }

    // }}}
    // {{{ _session_start()

    /**
     * Start a session.
     *
     * This function determines if a session has been started and if not, starts
     * the session, using the value from the registry for the name of the session.
     * It also disables the cookie params for sessions since they suck.
     *
     * @access private
     * @return void
     */
    function _session_start() {
        static $isStarted;
        
        if (!isset($isStarted)) {
            $registry =& FastFrame_Registry::singleton();
            // force usage of querystring for storing session ID
            ini_set('session.use_cookies', 0);
            // don't transparently track session ID, since we do it in all
            // our functions anyhow
            ini_set('session.use_trans_sid', 0);
            // set the cacheing 
            session_cache_limiter($registry->getConfigParam('session/cache', null, 'nocache'));

            // get the session name from the configuration or just use a default
            session_name($registry->getConfigParam('session/name', null, 'FastFrameSESSID'));
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
     * @return boolean
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
        return @md5($_SESSION['__auth__']['userID']);
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
        $registry =& FastFrame_Registry::singleton();
        FastFrame::redirect($registry->getConfigParam('session/safelogout'));
    }

    // }}}
    // {{{ _is_unique_username()

    /**
     * Checks to make sure a username is unique
     *
     * @param string $in_username The username to check
     * @param string $in_eeTable (optional) The ee table, if empty we use current_main
     *
     * @access private
     * @return bool True or false
     */
    function _is_unique_username($in_username, $in_eeTable = null)
    {
        $registry =& FastFrame_Registry::singleton();
        $db = $registry->getDataConnection();

        if (is_null($in_eeTable)) {
            $eeTable = 'current_main';
        }
        else {
            $eeTable = $in_eeTable;
        }

        // make sure it is unique in admin and ee tables 
        $query = 'SELECT 1 FROM `' . $registry->getCompanyDatabase() . '`.`admin` WHERE `f_loginUsername`=' . $db->quote($in_username);
        if ($db->getOne($query) != 1) {
            $query = 'SELECT 1 FROM `' . $registry->getCompanyDatabase() . '`.`' . $eeTable . '` WHERE `f_loginUsername`=' . $db->quote($in_username);
            if ($db->getOne($query) != 1) {
                return true;
            }
        }

        return false;
    }

    // }}}
}
?>
