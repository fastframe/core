<?php
/** $Id: Logout.php,v 1.4 2003/02/22 02:08:25 jrust Exp $ */
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
// {{{ requires

require_once dirname(__FILE__) . '/../Auth.php';
require_once dirname(__FILE__) . '/../Action.php';

// }}}
// {{{ class FF_Action_Logout

/**
 * The FF_Action_Login:: class handles the logout process 
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package ActionHandler 
 */

// }}}
class FF_Action_Logout extends FF_Action {
    // {{{ properties

    /**
     * The authentication object
     * @type object
     */
    var $o_auth;

    // }}}
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function FF_Action_Logout()
    {
        FF_Action::FF_Action();
        $this->o_auth =& FastFrame_Auth::singleton();
    }

    // }}}
    // {{{ run()
    
    /**
     * Verifies and submits the data to the database.
     *
     * @access public
     * @return void
     */
    function run()
    {
        // only if they were logged in should we tell them they were just logged out
        if ($this->o_auth->checkAuth()) {
            $this->o_auth->logout(null, true);
            $this->o_output->setMessage($this->getSuccessMessage());
        }

        $this->setSuccessActionId();
        return $this->o_nextAction;
    }

    // }}}
    // {{{ getSuccessMessage()

    /**
     * Gets the message for a successful logout 
     *
     * @access public
     * @return string The message
     */
    function getSuccessMessage()
    {
        return _('You have been logged out successfully.');
    }

    // }}}
    // {{{ setSuccessActionId()

    /**
     * Sets the actionId to invoke when a successful logout occurs. 
     *
     * @access public
     * @return void
     */
    function setSuccessActionId()
    {
        $this->o_nextAction->setNextActionId(ACTION_LOGIN);
    }

    // }}}
}
?>
