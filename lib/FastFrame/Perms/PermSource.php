<?php
/** $Id: PermSource.php,v 1.4 2003/02/08 00:10:56 jrust Exp $ */
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
// {{{ class  PermSource

/**
 * Abstract class describing a source pull permissions from
 *
 * @version Revision: 1.0 
 * @author  Greg Gilbert <greg@treke.net>
 * @access  public
 * @package FastFrame
 */

// }}}
class PermSource {
    // {{{ properties

    /**
     * The name assigned to this source
     * @type string
     */
    var $sourceName;

    // }}}
    // {{{ constructor
    
    /**
     * Initialize the AuthSource class
     *
     * Create an instance of the AuthSource class.  
     *
     * @param string $in_name The name of this auth source
     * @param array $in_params Additional parameters needed for authenticating
     *
     * @access public
     * @return object AuthSource object
     */
    function AuthSource($in_name, $in_params)
    {
        $this->sourceName = $in_name;
    }

    // }}}
    // {{{ create()

    /**
     * Creates an instance of a specific source
     *
     * When requesting a apermissions source, we do not need to know what type of 
     * object we want, just a source that can request a list of our permissions from.
     * If there is no source we can use, a source that never returns any permissions is
     * returned
     *
     * @access public
     * @return object Subclass of PermSource for the specified type, or PermSource if none is found 
     */
    function &create($in_type, $in_name, $in_params)
    {
        $s_permFile = dirname(__FILE__) . '/' . $in_type . '.php';
        // we assume they don't want to use perms in this case
        if (empty($in_type)) {
            return true;
        }
        elseif (!@file_exists($s_authFile)) {
            return FastFrame::fatal("$in_type is an invalid permission type.", __FILE__, __LINE__);
        }
        else {
            include_once $s_authFile;
        }
				
        $s_permClass= 'PermSource_' . $type;
				
        if (class_exists($s_permClass)) {
            $o_perms =& new $s_permClass($name, $params);
        }
        else {
            $o_perms =& new PermSource($name, $params);
        }

        return $o_perms; 
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
        return array();
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

    function getGroupList($in_userID, $in_app)
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
    function getPerms($in_userID, $in_appName)
    {
        return array();
    }

    // }}}
    // {{{ getName()

    /**
     * Return the name of this object
     *
     * Returns the name given to this object.
     *
     * @access public
     * @return string name given to this object by the programmer
     */
    function getName()
    {
        return $this->_sourceName;
    }

    // }}}
}
?>
