<?php
/** $Id: FormSubmit.php,v 1.4 2003/03/15 01:26:57 jrust Exp $ */
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

require_once dirname(__FILE__) . '/../Action.php';

// }}}
// {{{ class FF_Action_FormSubmit

/**
 * The FF_Action_FormSubmit:: class handles the processing of the form
 * information and then redirecting on to the next page (be it a problem or success page.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package ActionHandler 
 */

// }}}
class FF_Action_FormSubmit extends FF_Action {
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @param object $in_model The model object
     *
     * @access public
     * @return void
     */
    function FF_Action_FormSubmit(&$in_model)
    {
        FF_Action::FF_Action($in_model);
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
        $this->fillModelWithSubmitData();
        $o_result =& $this->validateInput();
        if (!$o_result->isSuccess()) {
            $o_result->addMessage($this->getProblemMessage());
            $this->o_output->setMessage($o_result->getMessages(), FASTFRAME_ERROR_MESSAGE);
            $this->setProblemActionId();
            return $this->o_nextAction;
        }

        $o_result =& $this->o_model->save($this->isUpdate());
        if ($o_result->isSuccess()) {
            // this makes sure the objectId is set if this was an add
            $_GET['objectId'] = $_POST['objectId'] = $this->o_model->getId();
            $this->o_output->setMessage($this->getSuccessMessage());
            $this->setSuccessActionId();
        }
        else {
            $o_result->addMessage($this->getProblemMessage());
            $this->o_output->setMessage($o_result->getMessages(), FASTFRAME_ERROR_MESSAGE);
            $this->setProblemActionId();
        }

        return $this->o_nextAction;
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
        if ($this->currentActionId == ACTION_EDIT_SUBMIT) {
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
        if ($this->currentActionId == ACTION_EDIT_SUBMIT) {
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
        if ($this->currentActionId == ACTION_EDIT_SUBMIT) {
            return _('There was an error in updating the data.');
        }
        else {
            return _('There was an error in adding the data.');
        }
    }

    // }}}
    // {{{ fillModelWithSubmitData()

    /**
     * Gets the submit data and fills it into the model object.
     *
     * @access public
     * @return void 
     */
    function fillModelWithSubmitData()
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
        if ($this->currentActionId == ACTION_EDIT_SUBMIT) {
            $this->o_nextAction->setNextActionId(ACTION_EDIT);
        }
        else {
            $this->o_nextAction->setNextActionId(ACTION_ADD);
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
        $this->o_nextAction->setNextActionId(ACTION_EDIT);
    }

    // }}}
    // {{{ validateInput()

    /**
     * Validates the input, usually using a validate object 
     *
     * @access public
     * @return object A result object 
     */
    function &validateInput()
    {
        // interface
    }

    // }}}
}
?>
