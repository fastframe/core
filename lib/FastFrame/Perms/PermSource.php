<?php
// {{{ class  PermSource

/**
 * Abstract class describing a source pull permissions from
 *
 * @version Revision: 1.0 
 * @author  Greg Gilbert <ggilbert@brooks.edu
 * @access  public
 * @package FastFrame
 */

// }}}
class PermSource {
    // {{{ properties
    /**
     * The name assigned to this source
     * @var string $sourceName
     */
    var $sourceName;

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
    function &create($type, $name, $params)
    {
        require_once(dirname(__FILE__) . '/' . $type . ".php");
				
        $permClass= "PermSource_" . $type;
				

        if ( class_exists($permClass)) {
            $object = &new $permClass( $name, $params);
        }
        else {
            $object = &new PermSource( $name, $params);
        }

        return $object; 
    }

    // }}}
    // {{{ constructor
    
    /**
     * Initialize the AuthSource class
     *
     * Create an instance of the AuthSource class.  
     *
     * @access public
     * @return AuthSource object
     */
    function AuthSource($name, $params)
    {
        $this->sourceName = $name;
    }

    // }}}
    // {{{ getAppList()
    /**
    *
    * Get a list of apps that the user has permission to log into
    *
    * This function returns an array applications that the user has
    * been granted permission to log into
    *
    * @access	
    * @see		??
    */
    function getAppList($in_user)
    {
        return array();
    }
    // }}}
    // {{{ getGroupList()
    /**
    *
    * Get a list of groups the user is a member of
    *
    * This function returns the list of groups that the specified user
    * is a member of
    *
    * @access public
    */
    function getGroupList ( $in_userID, $in_app)
    {
        return array();
    }
    // }}}
    // {{{ getPerms()
    /**
    *
    * Returns a list of permissions the user has
    *
    * This function returns the permissions that the specified user
    * has in the specified application. 
    *
    * @access public
    */
    function getPerms($in_userID,  $in_appName )
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
