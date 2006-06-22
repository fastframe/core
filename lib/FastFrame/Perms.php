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
// | Authors: Greg Gilbert <greg@treke.net>                               |
// |          Jason Rust <jrust@codejanitor.com>                          |
// +----------------------------------------------------------------------+

// }}}
// {{{ includes

require_once dirname(__FILE__) . '/Registry.php';
require_once dirname(__FILE__) . '/Auth.php';

// }}}
// {{{ constants

define('PERMS_TYPE_OR', 1);
define('PERMS_TYPE_AND', 2);

/**
 * The object permissions constants
 */
define('PERMS_READ', 1);
define('PERMS_DELETE', 2);
define('PERMS_EDIT', 3);
define('PERMS_ADD', 4);
define('PERMS_TYPE_USER', 5);
define('PERMS_TYPE_GROUP', 6);
define('PERMS_TYPE_PUBLIC', 7);
define('PERMS_TYPE_CREATOR', 8);

// }}}
// {{{ class FF_Perms

/**
 * A class for managing user permissions.  A gui and sql interface is implemented by the
 * FastFrame permissions application.
 *
 * @author		Greg Gilbert <greg@treke.net>
 * @author		Jason Rust <jrust@codejanitor.com>
 * @version	    Version 1.1
 * @package	    Perms
 */

// }}}
class FF_Perms {
    // {{{ properties

    /**
     * User Id of the user associated with this permissions object
     * @var int 
     */
    var $userId;

    /**
     * Caches user permissions
     * @var array
     */
    var $permsCache = array();

    /**
     * The array of super users defined in the config file
     * @var array 
     */
    var $superUsers = array();


    // }}}
    // {{{ constructor

    /**
     * Initializes the FF_Perms class
     *
     * @param string $in_userId The user Id to get perms for
     *
     * @access public
     * @return void
     */
    function FF_Perms($in_userId)
    {
        $this->userId = $in_userId;
        $this->setSuperUsers();
    }

    // }}}
    // {{{ factory()

    /**
     * Attempts to return a concrete Perms instance based on the type specified in the
     * registry.  Currently supported are 'dummy' which should be used when no permissions
     * support is wanted and 'permissions_app' which will use the library provided by the
     * permissions application. 
     *
     * @access public
     * @return object The newly created concrete Perms instance
     */
    function &factory()
    {
        static $a_instances;
        settype($a_instances, 'array');

        $o_registry =& FF_Registry::singleton();
        $s_type = $o_registry->getConfigParam('perms/source');
        $s_userId = FF_Auth::getCredential('userId');
        if (!isset($a_instances[$s_type]) || !isset($a_instances[$s_type][$s_userId])) {
            // the dummy type is just an instance of this class whose methods will
            // allow all users to do everything 
            if ($s_type == 'dummy') { 
                $a_instances[$s_type][$s_userId] =& new FF_Perms($s_userId);
            } 
            // use the model class from the permissions app to tell us info about perms
            elseif ($s_type == 'permissions_app') {
                require_once $o_registry->getAppFile('Model/Perms.php', 'permissions', 'libs');
                $a_instances[$s_type][$s_userId] =& new FF_Perms_PermissionsApp($s_userId);
            } 
            else {
                trigger_error('Invalid permissions source was defined in the config file.', E_USER_ERROR); 
            }
        }

        return $a_instances[$s_type][$s_userId];
    }

    // }}}
    // {{{ hasPerm()

    /**
     * Check whether a user has a certain permission for a specific app
     *
     * @param mixed $in_perm Either a string or an array of permissions
     *              to check.  If the last permission is '||' then we
     *              OR the permissions together (only ensuring the user
     *              has one of the specified perms), otherwise we AND
     *              them together (ensuring the user has all the
     *              specified perms). 
     * @param string $in_app The application the permission is associated with
     *               If not passed we use the current application
     *
     * @access public
     * @return bool Whether the user has the specified perms or not.
     */
    function hasPerm($in_perm, $in_app = null)
    {
        // dummy interface gives the user permission to everything
        return true;
    }

    // }}}
    // {{{ isSuperUser()

    /**
     * Determines if the user is a super user
     *
     * @return bool True if the user is a super user, false otherwise
     */
    function isSuperUser()
    {
        static $s_userName;
        if (!isset($s_userName)) {
            $s_userName = FF_Auth::getCredential('username');
        }

        return isset($this->superUsers[$s_userName]);
    }

    // }}}
    // {{{ setSuperUsers()

    /**
     * Sets the super users array
     *
     * @access public
     * @return void
     */
    function setSuperUsers()
    {
        // interface
    }

    // }}}
    // {{{ _getPermType()

    /**
     * Determines if the permission list is being ORed or ANDed.
     * Changes the array as needed.
     *
     * @param mixed $in_perm The permission string or array
     *
     * @access private
     * @return const The constant of the type of permission
     */
    function _getPermType(&$in_perm)
    {
        if (is_array($in_perm) && end($in_perm) == '||') {
            array_pop($in_perm);
            return PERMS_TYPE_OR;
        }

        return PERMS_TYPE_AND;
    }

    // }}}
    // {{{ _getPermCacheKey()

    /**
     * Gets the key we use to cache the perm results.  Handles both an array of perms
     * and a string.  Makes sure that the key is unique.
     *
     * @param mixed $in_perm Either a string or an array of permissions to check.
     * @param string $in_app The application the permission is associated with
     * @param const $in_permType The permission type
     *
     * @access private
     * @return string The key for the specified perm/app combo
     */
    function _getPermCacheKey($in_perm, $in_app, $in_permType)
    {
        if (is_array($in_perm)) {
            return $in_app . ':' . implode(':', $in_perm) . ':' . $in_permType;
        }
        else {
            return $in_app . ':' . $in_perm . ':' . $in_permType;
        }
    }

    // }}}
}
?>
