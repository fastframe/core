<?php
/** $Id: Logout.php,v 1.1 2003/02/06 18:48:10 jrust Exp $ */
// {{{ requires

require_once dirname(__FILE__) . '/GenericAction.php';

// }}}
// {{{ class ActionHandler_Logout

/**
 * The ActionHandler_Login:: class handles the logout process 
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lesser.html.
 *
 * @author  Jason Rust <jrust@rustyparts.com>
 * @version Revision: 1.0 
 * @access  public
 * @package ActionHandler 
 */

// }}}
class ActionHandler_Logout extends ActionHandler_GenericAction {
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
    function ActionHandler_Logout()
    {
        ActionHandler_GenericAction::ActionHandler_GenericAction();
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
        $this->o_action->takeAction();
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
        $this->o_action->setActionId(ACTION_LOGIN);
    }

    // }}}
}
?>
