<?php
// {{{ class  AuthSource

/**
 * Abstract class describing a source to authenticate against
 *
 *
 * @version Revision: 1.0 
 * @author  Greg Gilbert <ggilbert@brooks.edu
 * @access  public
 * @package FastFrame
 */

// }}}
class AuthSource {
    // {{{ properties
    /**
     * A unique name to identify an authenticated source
     * @var string $sourceName
     */
    var $sourceName;

    // }}}
    // {{{ create()

    /**
     * Creates an instance of a specific source
     *
     * When requesting an authentication source, we do not need to know what type of 
     * object we want, just a source that can authenticate against a preset target. 
     * If we don't have a source that can authenticate, a source that always fails is 
     * returned
     *
     * @access public
     * @return object Subclass of AuthSource for the specified type, or AuthSource if none is found 
     */
    function &create($type, $name, $params)
    {
        @include_once(dirname(__FILE__) . '/' . $type . ".php");
				
        $authClass= "AuthSource_" . $type;
				

        if ( class_exists($authClass)) {
            $object = &new $authClass( $name, $params);
        }
        else {
            $object = &new AuthSource( $name, $params);
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
    // {{{ authenticate()

    /**
     * Perform the login procedure.
     *
     * Authenticates the user name and password. This implementation will always
     * fail. 
     *
     * @access public
     * @return boolean determines if login was successfull
     */
    function authenticate($username, $password)
    {
        return false;
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
