<?php
/** $Id$ */
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
// | Authors: Jason Rust <jrust@codejanitor.com>                          |
// +----------------------------------------------------------------------+

// }}}
// {{{ class  FF_AuthSource_profile

/**
 * An authentication source for the profile application.  Uses it's Model class to get
 * the name of the table fields, so no extra parameters are needed.
 *
 * @version Revision: 1.0 
 * @author  Jason Rust <jrust@codejanitor.com>
 * @access  public
 * @package FastFrame
 */

// }}}
class FF_AuthSource_profile extends FF_AuthSource {
    // {{{ properties
    
    /**
     * The profile model object
     * @var object
     */
    var $o_model;
    
    // }}}
    // {{{ constructor

    /**
     * Initialize the FF_AuthSource_profile class
     *
     * @param string $in_name The name of this auth source
     * @param array $in_params Any extra parameters. 
     *
     * @access public
     * @return object FF_AuthSource_profile object
     */
    function FF_AuthSource_profile($in_name, $in_params)
    {
        FF_AuthSource::FF_AuthSource($in_name, $in_params);
        $this->passwordWritable = true;
        $this->usernameWritable = true;
        $o_registry =& FF_Registry::singleton();
        $this->serverName = $o_registry->getConfigParam('data/host');
        require_once $o_registry->getAppFile('Model/Profile.php', 'profile', 'libs');
        $this->o_model =& new FF_Model_ProfileProfile();
    }

    // }}}
    // {{{ authenticate()

    /**
     * Perform the login procedure.
     *
     * Authenticates the user name and password against the profile table 
     *
     * @param string $in_username The username to be authenticated.
     * @param string $in_password The corresponding password.
     *
     * @access public
     * @return boolean True if login was successful
     */
    function authenticate($in_username, $in_password)
    {
        $this->o_model->setPassword($in_password);
        $this->o_model->setUserName($in_username);
        $this->o_model->setAuthSource($this->getName());
        return $this->o_model->authenticate();
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
        $this->o_model->setId($in_userId);
        $this->o_model->setPassword($in_newPassword);
        return $this->o_model->updatePassword();
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
        $this->o_model->setId($in_userId);
        $this->o_model->setUserName($in_newUserName);
        return $this->o_model->updateUserName();
    }

    // }}}
}
?>
