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
// {{{ requires

require_once dirname(__FILE__) . '/../Auth.php';
require_once dirname(__FILE__) . '/../Action.php';

// }}}
// {{{ class FF_Action_LoginSubmit

/**
 * The FF_Action_Login:: class handles the processing of the login form 
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package ActionHandler 
 */

// }}}
class FF_Action_LoginSubmit extends FF_Action {
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @param object $in_model The model object
     *
     * @access public
     * @return void
     */
    function FF_Action_LoginSubmit(&$in_model)
    {
        FF_Action::FF_Action($in_model);
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
        if (FF_Auth::authenticate(
            FastFrame::getCGIParam('username', 'p'), 
            FastFrame::getCGIParam('password', 'p'))) {
            // if the profile application is installed then set the user Id
            if ($this->o_registry->hasApp('profile')) {
                require_once $this->o_registry->getAppFile('Model/Profile.php', 'profile', 'libs');
                $o_profileModel =& new FF_Model_ProfileProfile();
                $s_userId = $o_profileModel->getIdByUsername(FF_Auth::getCredential('username'));
                // if they were able to log on, but don't have a user id, then they logged on
                // with an auth source other than the profile database, so give them an empty profile
                if (is_null($s_userId)) {
                    $o_profileModel->setUsername(FF_Auth::getCredential('username'));
                    $o_profileModel->setAuthSource(FF_Auth::getCredential('authSource'));
                    $o_result =& $o_profileModel->addMinimalProfile();
                    if (!$o_result->isSuccess()) {
                        $this->o_output->setMessage(_('Error encountered in creating your profile.'));
                        $this->o_output->setMessage($o_result->getMessages());
                        $this->setProblemActionId();
                        return $this->o_nextAction;
                    }
                    else {
                        $s_userId = $o_profileModel->getId();
                    }
                }

                $o_profileModel->fillById($s_userId);
                FF_Auth::setCredential('isProfileComplete', $o_profileModel->isProfileComplete());
                $s_theme = $o_profileModel->getTheme();
                $_SESSION['advancedList'] = $o_profileModel->getListMode();
            }
            else {
                $s_userId = FF_Auth::getCredential('username');
                $s_theme = $this->o_registry->getConfigParam('general/default_theme');
            }

            FF_Auth::setCredential('userId', $s_userId); 
            FF_Auth::setCredential('theme', $s_theme); 
            if ($this->o_output->getTheme() != $s_theme) {
                $this->o_output->reset();
                $this->o_output->load($s_theme);
            }

            // login is successful proceed to initial app or where we came from
            $s_redirectURL = FastFrame::getCGIParam('loginRedirect', 'gp', false);
            if (empty($s_redirectURL)) {
                $s_initialApp = $this->o_registry->getConfigParam('general/initial_app', 'login');
                if ($s_initialApp != 'login') {
                    $this->o_nextAction->setNextAppId($s_initialApp);
                    $this->o_nextAction->setNextModuleId(false);
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

        return $this->o_nextAction;
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
        $this->o_nextAction->setNextActionId(ACTION_LOGIN);
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
        $this->o_nextAction->setNextActionId(ACTION_DISPLAY);
    }

    // }}}
}
?>
