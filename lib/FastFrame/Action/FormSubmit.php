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
    // {{{ properties

    /**
     * The action id used in edit mode
     * @var string
     */
    var $editActionId = ACTION_EDIT_SUBMIT;

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
        if (!$this->checkPerms() || $this->doMultiPageForm()) {
            return $this->o_nextAction;
        }

        $o_result =& $this->validateInput();
        if (!$o_result->isSuccess()) {
            $o_result->addMessage($this->getProblemMessage());
            $this->o_output->setMessage($o_result->getMessages(), FASTFRAME_ERROR_MESSAGE);
            $this->setProblemActionId();
            return $this->o_nextAction;
        }

        if ($o_result->hasMessages()) {
            $this->o_output->setMessage($o_result->getMessages(), FASTFRAME_WARNING_MESSAGE);
        }

        $o_result =& $this->save();
        if ($o_result->isSuccess()) {
            // This makes sure the objectId is set if this was an add
            FF_Request::setParam('objectId', $this->o_model->getId(), 'gp');
            // This lets any action that comes next know that it was a success
            FF_Request::setParam('submitWasSuccess', 1, 'g');
            $this->o_output->setMessage($this->getSuccessMessage(), FASTFRAME_SUCCESS_MESSAGE, true);
            $this->setSuccessActionId();
            if ($o_result->hasMessages()) {
                $this->o_output->setMessage($o_result->getMessages(), FASTFRAME_WARNING_MESSAGE);
            }
        }
        else {
            FF_Request::setParam('submitWasSuccess', 0, 'g');
            $o_result->addMessage($this->getProblemMessage());
            $this->o_output->setMessage($o_result->getMessages(), FASTFRAME_ERROR_MESSAGE, true);
            $this->setProblemActionId();
        }

        return $this->o_nextAction;
    }

    // }}}
    // {{{ save()

    /**
     * Instructs the model to save itself.
     *
     * @access public
     * @return object The result object
     */
    function &save()
    {
        return $this->o_model->save($this->isUpdate());
    }

    // }}}
    // {{{ isUpdate()

    /**
     * Is this an update (return true) or add (return false)?
     *
     * @access public
     * @return bool True if is an update, false if it is an add 
     */
    function isUpdate()
    {
        return ($this->currentActionId == $this->editActionId) ? true : false; 
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
        if ($this->isUpdate()) {
            return sprintf(_('Updated the %s successfully.'), $this->getSingularText());
        }
        else {
            return sprintf(_('Added the %s successfully.'), $this->getSingularText());
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
        if ($this->isUpdate()) {
            return sprintf(_('There was an error updating the %s.'), $this->getSingularText());
        }
        else {
            return sprintf(_('There was an error adding the %s.'), $this->getSingularText());
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
    // {{{ validateInput()

    /**
     * Validates the input, usually using a validate object 
     *
     * @access public
     * @return object A result object 
     */
    function &validateInput()
    {
        return new FF_Result();
    }

    // }}}
    // {{{ checkPerms()

    /**
     * Check the permissions on this action.
     *
     * @access public
     * @return bool True if everything is ok, false if a new action has been set
     */
    function checkPerms()
    {
        return true;
    }

    // }}}
    // {{{ doMultiPageForm()

    /**
     * If this is a multi-page form this method will set the next action for the next page
     * in the form and export the model to a session var. 
     *
     * @access public
     * @return bool True if it is a multi-page form, false otherwise
     */
    function doMultiPageForm()
    {
        return false;
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
        if ($this->isUpdate()) {
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
    // {{{ getSingularText()

    /**
     * Gets the text that describes a singular version of the item manipulated
     *
     * @access public
     * @return string The singular text
     */
    function getSingularText()
    {
        return _('data');
    }

    // }}}
}
?>
