<?php
/** $Id: Item.php 417 2003-04-14 18:03:12Z jrust $ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2009 The Codejanitor Group                        |
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
// | Authors: Greg Gilbert <ggilbert@codejanitor.com>                     |
// +----------------------------------------------------------------------+

// }}}
// {{{ class FF_ActionHandlerConfig

/**
 * The FF_ActionHandlerConfig:: is the setup class for the action handler
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @author  Greg Gilbert <ggilbert@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package FastFrame 
 */

// }}}
class FF_ActionHandlerConfig {
    // {{{ properties

    /**
     * The action handler we are working with
     * @var string 
     */
    var $actionHandler;

    // }}}
    // {{{ constructor

    /**
     * Class initialization
     *
     * @access public
     * @return void
     */
    function FF_ActionHandlerConfig(&$in_actionHandler)
    {
        $this->actionHandler =& $in_actionHandler;
    }

    // }}}
    // {{{ loadConfig()

    /**
     * Configures actions for the Action Handler and loads the model object
     *
     * @access public
     * @return void
     */
    function loadConfig()
    {
        // interface
    }

    // }}}
    // {{{ checkPerms()

    /**
     * Checks module-wide permissions.  If there is a permission proble
     * it will set the current action id to a safe page and set an error
     * message.
     *
     * @access public
     * @return bool True if everything is ok, false otherwise.
     */
    function checkPerms()
    {
        return true;
    }

    // }}}
    // {{{ hasCheckAuth

    /**
     * Checks whether or not this function as a checkAuth() implementation 
     *
     * @access public
     * @return bool
     */
    function hasCheckAuth() 
    {
        return false;
    }

    //}}}
    // {{{ checkAuth()

    /**
     * Initializes the auth object and makes sure that the page can proceed because the
     * authentication passes. Used to override the ActionHandler::checkAuth() function 
     *
     * @access public
     * @return void
     */
    function checkAuth() 
    {
        return false;
    }

    // }}}
}
?>
