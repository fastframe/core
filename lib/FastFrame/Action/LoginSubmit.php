<?php
/** $Id: LoginSubmit.php,v 1.3 2003/02/12 20:51:43 jrust Exp $ */
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

require_once dirname(__FILE__) . '/Action.php';

// }}}
// {{{ class ActionHandler_LoginSubmit

/**
 * The ActionHandler_Login:: class handles the processing of the login form 
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package ActionHandler 
 */

// }}}
class ActionHandler_LoginSubmit extends ActionHandler_Action {
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
    function ActionHandler_LoginSubmit()
    {
        ActionHandler_Action::ActionHandler_Action();
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
        if ($this->o_auth->authenticate(
            FastFrame::getCGIParam('username', 'p'), 
            FastFrame::getCGIParam('password', 'p'))) {
            // login is successful proceed to initial app or where we came from
            $s_redirectURL = FastFrame::getCGIParam('loginRedirect', 'gp', false);
            if (empty($s_redirectURL)) {
                $s_initialApp = $this->o_registry->getConfigParam('general/initial_app', 'login');
                if ($s_initialApp != 'login') {
                    $s_redirectURL = FastFrame::url($this->o_registry->getAppFile('index.php', $s_initialApp, '', FASTFRAME_WEBPATH), true);
                    FastFrame::redirect($s_redirectURL);
                }
                else {
                    $this->setSuccessActionId();
                }
            }
            else {
                FastFrame::redirect($s_redirectURL);
            }
        }
        else {
            $this->o_output->setMessage($this->getProblemMessage(), FASTFRAME_ERROR_MESSAGE);
            $this->setProblemActionId();
        }

        $this->o_action->takeAction();
    }

    // }}}
    // {{{ getProblemMessage()

    /**
     * Gets the message for when the login fails. 
     *
     * @access public
     * @return string The message
     */
    function getProblemMessage()
    {
        return _('Login failed.  Please check your username and password and try again.');
    }

    // }}}
    // {{{ setProblemActionId()

    /**
     * Sets the actionId to invoke when an error occurs in the login process 
     *
     * @access public
     * @return void
     */
    function setProblemActionId()
    {
        $this->o_action->setActionId(ACTION_LOGIN);
    }

    // }}}
    // {{{ setSuccessActionId()

    /**
     * Sets the actionId to invoke when a successful update occurs. 
     *
     * @access public
     * @return void
     */
    function setSuccessActionId()
    {
        $this->o_action->setActionId(ACTION_DISPLAY);
    }

    // }}}
}
?>
