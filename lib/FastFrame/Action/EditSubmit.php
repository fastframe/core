<?php
/** $Id: EditSubmit.php,v 1.1 2003/02/06 18:48:10 jrust Exp $ */
// {{{ requires

require_once dirname(__FILE__) . '/GenericAction.php';

// }}}
// {{{ class ActionHandler_EditSubmit

/**
 * The ActionHandler_Edit:: class handles the processing of the form information. 
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
class ActionHandler_EditSubmit extends ActionHandler_GenericAction {
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function ActionHandler_EditSubmit()
    {
        ActionHandler_GenericAction::ActionHandler_GenericAction();
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
        if ($this->validateForm()) {
            if ($this->updateData()) {
                $this->o_output->setMessage($this->getSuccessMessage());
                $this->setSuccessActionId();
            }
            else {
                $this->o_output->setMessage($this->getProblemMessage(), FASTFRAME_ERROR_MESSAGE);
                $this->setProblemActionId();
            }
        }
        else {
            $this->o_output->setMessage($this->getValidationMessage(), FASTFRAME_ERROR_MESSAGE);
            $this->setProblemActionId();
        }

        $this->takeAction();
    }

    // }}}
    // {{{ updateData()
        
    /**
     * Updates the data with the new form values.
     *
     * @access public
     * @return bool True if the update succeeded, false otherwise
     */
    function updateData()
    {
        return $this->o_application->updateData($GLOBALS['_' . $this->formAction]);
    }

    // }}}
    // {{{ validateForm()

    /**
     * Validates the input from the form based on the QuickForm rules
     * 
     * @access public
     * @return bool True if the validation succeeded, false otherwise
     */
    function validateForm()
    {
        // have to reconstruct the form with its rules
        $this->setActionId(ACTION_EDIT);
        $this->takeAction();
        $this->setActionId(ACTION_EDIT_SUBMIT);
        return $this->o_form->validate();
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
        return _('Updated the data successfully.');
    }

    // }}}
    // {{{ getValidationMessage()

    /**
     * Gets the message for a validation problem 
     *
     * @access public
     * @return string The message
     */
    function getValidationMessage()
    {
        return _('There was an error in validating your input.');
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
        return _('There was an error in updating your information.');
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
        $this->setActionId(ACTION_EDIT);
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
        $this->setActionId(ACTION_EDIT);
    }

    // }}}
}
?>
