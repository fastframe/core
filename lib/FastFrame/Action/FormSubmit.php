<?php
/** $Id: FormSubmit.php,v 1.1 2003/02/12 20:49:11 jrust Exp $ */
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
// {{{ class ActionHandler_FormSubmit

/**
 * The ActionHandler_FormSubmit:: class handles the processing of the form
 * information and then redirecting on to the next page (be it a problem or success page.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package ActionHandler 
 */

// }}}
class ActionHandler_FormSubmit extends ActionHandler_Action {
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function ActionHandler_FormSubmit()
    {
        ActionHandler_Action::ActionHandler_Action();
    }

    // }}}
    // {{{ run()
    
    /**
     * Submits the data to the database and either reports success or failure
     *
     * @access public
     * @return void
     */
    function run()
    {
        $result = $this->o_application->saveData($this->getSubmitData(), $this->isUpdate());
        if ($result === false) {
            $this->o_output->setMessage($this->getProblemMessage(), FASTFRAME_ERROR_MESSAGE);
            $this->setProblemActionId();
        }
        else {
            // this makes sure the objectId is set if this was an add
            $_GET['objectId'] = $_POST['objectId'] = $result;
            $this->o_output->setMessage($this->getSuccessMessage());
            $this->setSuccessActionId();
        }

        $this->o_action->takeAction();
    }

    // }}}
    // {{{ isUpdate()

    /**
     * Is this an update (return true) or add (return false)
     *
     * @access public
     * @return bool True if we should update, false if we should add 
     */
    function isUpdate()
    {
        if ($this->o_action->getActionId() == ACTION_EDIT_SUBMIT) {
            return true;
        }
        else {
            return false;
        }
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
        if ($this->o_action->getActionId() == ACTION_EDIT_SUBMIT) {
            return _('Updated the data successfully.');
        }
        else {
            return _('Added the data successfully.');
        }
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
        if ($this->o_action->getActionId() == ACTION_EDIT_SUBMIT) {
            return _('There was an error in updating the data.');
        }
        else {
            return _('There was an error in adding the data.');
        }
    }

    // }}}
    // {{{ getSubmitData()

    /**
     * Gets the submit data that should be passed on to the save method
     *
     * @access public
     * @return array An array of 'field' => 'value'
     */
    function getSubmitData()
    {
        // interface
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
        if ($this->o_action->getActionId() == ACTION_EDIT_SUBMIT) {
            $this->o_action->setActionId(ACTION_EDIT);
        }
        else {
            $this->o_action->setActionId(ACTION_ADD);
        }
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
        $this->o_action->setActionId(ACTION_EDIT);
    }

    // }}}
}
?>
