<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2005 The Codejanitor Group                        |
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
// {{{ class  FF_AuthSource

/**
 * Abstract class describing a source to authenticate against
 *
 * @version Revision: 1.0 
 * @author  Greg Gilbert <greg@treke.net>
 * @access  public
 * @package Auth 
 */

// }}}
class FF_AuthSource {
    // {{{ properties

    /**
     * A unique name to identify this source
     * @var string
     */
    var $name;

    /**
     * The server name this source authenticates against
     * @var string
     */
    var $serverName;

    /**
     * The capabilities of the auth source so we know what it can do.
     * @var array
     */
    var $capabilities = array(
            'resetpassword' => false,
            'updateusername' => false,
            'transparent' => false);

    /**
     * The result object
     * @var object
     */
    var $o_result;

    // }}}
    // {{{ constructor
    
    /**
     * Initialize the AuthSource class
     *
     * @param string $in_name The name of this auth source
     * @param array $in_params Additional parameters needed for authenticating
     *
     * @access public
     * @return void 
     */
    function FF_AuthSource($in_name, $in_params)
    {
        $this->name = $in_name;
        $this->o_result = new FF_Result();
        $this->o_result->setSuccess(false);
    }

    // }}}
    // {{{ factory()

    /**
     * Creates an instance of a specific source
     *
     * When requesting an authentication source, we do not need to know what type of 
     * object we want, just a source that can authenticate against a preset target. 
     * If we don't have a source that can authenticate, a source that always fails is 
     * returned
     *
     * @param string $in_type The type of auth source to instantiate
     * @param string $in_name The name to give to the auth source
     * @param array $in_params Any extra params the auth source needs
     *
     * @access public
     * @return object Subclass of AuthSource for the specified type, or AuthSource if none is found 
     */
    function &factory($in_type, $in_name, $in_params)
    {
        $pth_authFile = dirname(__FILE__) . '/' . $in_type . '.php';
        if (!file_exists($pth_authFile)) {
            $o_auth =& new FF_AuthSource($in_name, $in_params);
            return $o_auth;
        }

        require_once $pth_authFile;
        $s_authClass = 'FF_AuthSource_' . $in_type;
        $o_auth =& new $s_authClass($in_name, $in_params);
        return $o_auth;
    }

    // }}}
    // {{{ authenticate()

    /**
     * Perform the login procedure.
     *
     * @access public
     * @return object A result object
     */
    function authenticate($username, $password)
    {
        return $this->o_result;
    }

    // }}}
    // {{{ transparent()

    /**
     * Perform a transparent login procedure.
     *
     * @access public
     * @return bool determines if login was successfull
     */
    function transparent()
    {
        return false;
    }

    // }}}
    // {{{ getName()

    /**
     * Return the name of this object
     *
     * @access public
     * @return string name given to this object
     */
    function getName()
    {
        return $this->name;
    }

    // }}}
    // {{{ getServerName()

    /**
     * Gets the server that this source authenticates against
     *
     * @access public
     * @return string The server name
     */
    function getServerName()
    {
        return $this->serverName;
    }

    // }}}
    // {{{ hasCapability()

    /**
     * Gets the specified capability
     *
     * @param $in_name The name of the capability
     *
     * @access public
     * @return bool The capability 
     */
    function hasCapability($in_name)
    {
        return $this->capabilities[$in_name];
    }

    // }}}
    // {{{ updatePassword()

    /**
     * Changes the password on the auth source
     *
     * @param int $in_userId The userId for the user being changed 
     * @param string $in_newPassword The new password
     *
     * @access public
     * @return object The result object
     */
    function updatePassword($in_userId, $in_newPassword)
    {
        // interface
    }

    // }}}
    // {{{ updateUserName()

    /**
     * Changes the username on the auth source
     *
     * @param int $in_userId The userId for the user being changed 
     * @param string $in_newUserName The new username
     *
     * @access public
     * @return object The result object
     */
    function updateUserName($in_userId, $in_newUserName)
    {
        // interface
    }

    // }}}
}
?>
