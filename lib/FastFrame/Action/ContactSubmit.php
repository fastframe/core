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
// | Authors: Jason Rust <jrust@codejanitor.com>                          |
// +----------------------------------------------------------------------+

// }}}
// {{{ requires

require_once dirname(__FILE__) . '/../Action.php';

// }}}
// {{{ class FF_Action_ContactSubmit

/**
 * The FF_Action_ContactSubmit:: class handles the processing of the form
 * information and sending the email to the help address.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package ActionHandler
 */

// }}}
class FF_Action_ContactSubmit extends FF_Action {
    // {{{ run()
    
    /**
     * Submits the data to the database and either reports success or failure
     *
     * @access public
     * @return void
     */
    function run()
    {
        if (!$this->o_registry->getConfigParam('help/show_link')) {
            $this->setProblemActionId();
            $this->o_output->setMessage($this->getProblemMessage(), FASTFRAME_ERROR_MESSAGE);
            return $this->o_nextAction;
        }

        require_once 'Mail.php';
        $o_mail =& Mail::factory($this->o_registry->getConfigParam('mailer/type'), 
                $this->o_registry->getConfigParam('mailer/params'));
        if (PEAR::isError($o_mail)) {
            $this->o_output->setMessage($o_mail->getMessage(), FASTFRAME_ERROR_MESSAGE);
            $this->setProblemActionId();
            return $this->o_nextAction;
        }

        $a_headers = array();
        $a_headers['Subject'] = $this->getSubjectPrepend() . FF_Request::getParam('subject', 'p');
        $a_headers['From'] = FF_Request::getParam('email', 'p');
        $s_body = _('Name: ') . FF_Request::getParam('name', 'p') . "\n";
        $s_body .= _('Email: ') . FF_Request::getParam('email', 'p') . "\n";
        $s_body .= wordwrap(htmlspecialchars(FF_Request::getParam('message', 'p')));
        $s_body .= $this->getExtraInfo();
        $o_mailResult = $o_mail->send($this->o_registry->getConfigParam('help/email'), $a_headers, $s_body);
        if (PEAR::isError($o_mailResult)) {
            FF_Request::setParam('submitWasSuccess', 0, 'g');
            $this->o_output->setMessage($o_mailResult->getMessage(), FASTFRAME_ERROR_MESSAGE);
            $this->setProblemActionId();
        }
        else {
            // This lets any action that comes next know that it was a success
            FF_Request::setParam('submitWasSuccess', 1, 'g');
            $this->o_output->setMessage($this->getSuccessMessage(), FASTFRAME_SUCCESS_MESSAGE, true);
            $this->setSuccessActionId();
        }

        return $this->o_nextAction;
    }

    // }}}
    // {{{ getSuccessMessage()

    /**
     * Gets the message for a successful update
     *
     * @access public
     * @return string The message
     */
    function getSuccessMessage()
    {
        return _('Your message was sent successfully.');
    }

    // }}}
    // {{{ getProblemMessage()

    /**
     * Gets the message for when updating the data fails
     *
     * @access public
     * @return string The message
     */
    function getProblemMessage()
    {
        return _('There was an error sending your message.');
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
        $this->o_nextAction->setNextActionId(ACTION_CONTACT);
    }

    // }}}
    // {{{ setProblemActionId()

    /**
     * Sets the actionId to invoke when an error occurs in the update of the database. 
     *
     * @access public
     * @return void
     */
    function setProblemActionId()
    {
        $this->o_nextAction->setNextActionId(ACTION_CONTACT);
    }

    // }}}
    // {{{ getExtraInfo()

    /**
     * Gets extra information to be appended to the message.
     *
     * @access public
     * @return string The data to be appended.
     */
    function getExtraInfo()
    {
        $s_text = "\n\n";
        $s_text .= _('User Information:') . "\n";
        $s_text .= "\t" . _('Name') . ': ' . FF_Request::getParam('name', 'p') . "\n"; 
        $s_text .= "\t" . _('Email') . ': ' . FF_Request::getParam('email', 'p') . "\n"; 
        $s_text .= "\t" . _('Phone') . ': ' . FF_Request::getParam('phone', 'p') . "\n"; 
        if (FF_Auth::checkAuth()) {
            $s_text .= "\t" . _('Username') . ': ' . FF_Auth::getCredential('username') . "\n"; 
        }

        $s_text .= "\t" . _('Server Name') . ': ' . $_SERVER['SERVER_NAME'] . "\n";
        $s_text .= "\t" . _('Referer') . ': ' . FF_Request::getParam('http_referer', 'p') . "\n";
        $s_text .= "\t" . _('User Agent') . ': ' . @$_SERVER['HTTP_USER_AGENT'];
        return $s_text;
    }

    // }}}
    // {{{ getSubjectPrepend()

    /**
     * Gets the string to prepend to the subject
     *
     * @access public
     * @return string The data to prepend to the subject
     */
    function getSubjectPrepend()
    {
        return _('[FastFrame Help Request]') . ' ';
    }
    
    // }}}
}
?>
