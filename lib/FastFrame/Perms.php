<?php
/** $Id: Perms.php,v 1.5 2003/02/08 00:10:54 jrust Exp $ */
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
// | Authors: Greg Gilbert <greg@treke.net>                               |
// +----------------------------------------------------------------------+

// }}}
// {{{ includes

require_once dirname(__FILE__) . '/Perms/PermSource.php';

// }}}
// {{{ class FastFrame_Perms
/**
 * A class for managing user permissions
 *
 * @author		Greg Gilbert <greg@treke.net>
 * @version	    Version 1.0
 * @package		FastFrame
 * @see			User Auth
 */

// }}}
class FastFrame_Perms {
    // {{{ properties

    /**
     * User ID of the user associated with this permissions object
     * @type    string
     * @access	private
     */
    var $_userid;

    /**
     * Caches user permissions so we do not need to look them up
     * for each request
     * @type    array
     * @access	private
     */
    var $_permCache;

    /**
     * Provides an actual interface to the data source for permissions
     * @type    object
     * @access	private
     */
    var $_permSource;

    // //}}}
    // {{{ constructor

    /**
     * Initializes the FastFrame_Perms class
     *
     * This function initializes the permissions class for the
     * for the specified user.
     *
     * @param string $in_userID (optional) The user ID to get perms for. If not passed in we
     *               grab the info from Auth.
     *
     * @access public
     * @return void
     */
    function FastFrame_Perms($in_userID = null)
    {
        if (is_null($in_userID)) {
            $this->_userID = FastFrame_Auth::getCredential('userID');
        }
        else {
            $this->_userID = $in_userID;
        }

        $o_registry =& FastFrame_Registry::singleton();

        $a_source = $o_registry->getConfigParam('perms/source');
        $this->permSource =& PermSource::create($a_source['type'], $s_name, $a_source['params']);
    }

    // }}}
    // {{{ getAppList()

    /**
     * Returns a list of apps the user has access to
     *
     * This function returns a list of FastFrame Apps that are 
     * installed in this framework that the user as LOGIN 
     * permissions for. 
     *
     * @access public
     * @return array The list of apps that the user has Login perms for
     */
    function getAppList()
    {
        $o_registry =& FastFrame_Registry::singleton();

        if ($this->isSuperUser()) {
            return $o_registry->getApps();
        }
        else {
            return $this->permSource->getAppList();
        }
    }

    // }}}
    // {{{ getGroupList()

    /**
     * Returns a list of groups that the user belongs to in the current app
     *
     * This function returns an array containting all groups that the current 
     * user has any permissions in
     *
     * @access public
     * @return array List of all groups that the user is in
     */
    function getGroupList()
    {
        return $this->permSource->getGroupList();
    }
    // }}}
    // {{{ hasPerm()

    /**
     * Check whether a user has the needed permission
     *
     * This function looks up whether a user is in a group
     * with the requested permissions in the current app.
     *
     * @param $in_perms mixed Either a string or array of permissions to check.
     *
     * @access public
     * @return bool Whether the user has the specified perms or not.
     */
    function hasPerms($in_perms)
    {
        $b_hasPerms = false;
        $s_app = FastFrame::getCurrentApp();

        if ($this->isSuperUser()) {
            return true;
        }

        if (!is_array($this->_permCache[$s_app]) && 
            (count($this->_permCache[$s_app]) == 0)) {

            // We dont have permissions cached, so we need to get them
            $this->_permCache[$s_app] = $this->_permSource->getPerms($s_app);
        }


        // $in_perms may be an array of permissions, or a single string
        if (is_array($in_perms)) {
            $i_foundCount = 0;

            // Look for each of the requested permissions
            foreach ($in_perms as $s_perm) {
                if (in_array($this->_permCache[$s_app], $s_perm)) {
                    $i_foundCount++;
                }

                if ($i_foundCount == count($in_perms)) {
                    $b_hasPerms = true;
                }
            }
        }
        else {
            // Look up the one requested permission
            if (in_array($this->permCache[$s_app], $in_perms)) {
                $b_hasPerms = true;
            }
        }

        return $b_hasPerms;
    }

    // }}}
    // {{{ getUserClasses()

    /**
     * Returns an array of the available user classes
     *
     * @access public
     * @return array The array of 'user class description' => 'user class value'
     */
    function getUserClasses()
    {
        return array(
            'UC_GUEST' => UC_GUEST,
            'UC_USER' => UC_USER,
            'UC_ADMIN_VIEWER' => UC_ADMIN_VIEWER,
            'UC_ADMIN_EDITOR' => UC_ADMIN_EDITOR,
            'UC_ADMIN' => UC_ADMIN,
            'UC_ROOT' => UC_ROOT,
        );
    }

    // }}}
    // {{{ getUserClass()

    /**
     * Returns the user class that the user is a member of
     *
     * @access public
     * @return int The user class
     */
    function getUserClass()
    {
        // [!] not done yet [!]
        return UC_ROOT;
    }

    // }}}
}
