<?php
/** $Id: sql.php,v 1.3 2003/01/22 02:01:46 jrust Exp $ */
// {{{ class  PermSource_sql

/**
 * A Permissions source that reads it's data from an SQL database
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lesser.html.
 *
 * @version Revision: 1.0 
 * @author  Greg Gilbert <ggilbert@brooks.edu
 * @access  public
 * @package FastFrame
 */

// }}}
class PermSource_sql {
    // {{{ properties

    /**
     * The name assigned to this source
     * @type string
     */
    var $sourceName;

    /**
     * The DSN that we will use to connect to our database
     * @access	private
     */
    var $_dsn;

    // }}}
    // {{{ constructor

    /**
     * Initialize the PermSource class
     *
     * Create an instance of the PermSource_sql class, 
     *
     * @param string $in_name The name of this auth source
     * @param array $in_params Additional parameters needed for authenticating
     *
     * @access public
     * @return object PermSource_sql object
     */
    function PermSource_sql($in_name, $in_params)
    {
        $this->sourceName = $in_name;
        $this->_dsn = $s_type . '://' . $in_params['username'] . ':' . $in_params['password'] . '@' . $in_params['host'] . '/' . $in_params['database'];
    }

    // }}}
    // {{{ getAppList()

    /**
     * Get a list of apps that the user has permission to log into
     *
     * This function returns an array applications that the user has
     * been granted permission to log into
     *
     * @param string $in_user The user ID
     *
     * @access public
     */
    function getAppList($in_user)
    {
        $o_db->_db =& $this->_openDatabase();
        $s_sql = ' SELECT `perms`.`perm` FROM `perms`, `groups` WHERE
            `groups`.`group`=`perms`.`group`
            AND `perms`.`app`=`groups`.`app`
            AND `perms`.`perm`=`login` 
            AND `groups`.`user`=' . quote($this->_userid);

        $a_result = $o_db->getCol($s_sql,'perm');

        if (DB::isError($a_result)) {
            FastFrame::fatal($a_result, __FILE__, __LINE__);
        }

        $o_db->disconnect();
        return $a_result;
    }
    // }}}
    // {{{ getGroupList()

    /**
     * Get a list of groups the user is a member of
     *
     * This function returns the list of groups that the specified user
     * is a member of
     *
     * @access public
     */
    function getGroupList ($in_userID, $in_app)
    {
        return array();
    }

    // }}}
    // {{{ getPerms()

    /**
     * Returns a list of permissions the user has
     *
     * This function returns the permissions that the specified user
     * has in the specified application. 
     *
     * @access public
     */
    function getPerms($in_userID,  $in_appName )
    {
        $a_allowed = $this->getPermissions($in_userID, $in_appName, false);
        $a_denied = $this->getPermissions($in_userID, $in_appName, true);
        return array_diff($a_allowed, $a_denied);
    }

    // }}}
    // {{{ _getPermissions()

    /**
     * List denied permissions
     *
     * This function returns an array containing the permissions that have been 
     * explicitly denied or granted to the user.
     *
     * @access  private
     */
    function _getPermissions($in_userID, $in_app, $in_denied)
    {
        $o_db =& $this->_openDatabase();

        if ($in_denied) {
            $i_getDenied=1;
        }
        else {
            $i_getDenied=0;
        }

        $s_sql = ' SELECT `perms`.`perm` FROM `perms`, `groups` WHERE
        `groups`.`group`=`perms`.`group`
        AND `perms`.`app`=`groups`.`app`
        AND `groups`.`user`=' . quote( $in_userID) . '
        AND `groups`.`app`=' . quote( $in_app) . '
        AND `denied`=' . $i_getDenied;

        $a_result = $o_db->getCol($s_sql,'perm');

        if (DB::isError($a_result)) {
            FastFrame::fatal($a_result, __FILE__, __LINE__);
        }

        $o_db->disconnect();
        return $a_result;
    }

    // }}}
    // {{{ _openDatabase()

    /**
     * This function creates a database connection 
     *
     * This is the long description for the Class
     *
     * @access public
     */
    function &_openDatabase()
    {
        $o_db = DB::connect($this->_dsn);
        if (DB::isError($o_db)) {
            FastFrame::fatal($a_dataConnection[$s_app], __FILE__, __LINE__);
        }   

        return $o_db;
    }

    // }}}
}

