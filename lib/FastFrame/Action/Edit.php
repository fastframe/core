<?php
/** $Id: Edit.php,v 1.1 2003/02/06 18:48:10 jrust Exp $ */
// {{{ requires

require_once dirname(__FILE__) . '/GenericForm.php';

// }}}
// {{{ class ActionHandler_Edit

/**
 * The ActionHandler_Edit:: class Handles the setting up of the form that is needed to
 * edit an object.
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
class ActionHandler_Edit extends ActionHandler_GenericForm {
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function ActionHandler_Edit()
    {
        ActionHandler_GenericForm::ActionHandler_GenericForm();
    }

    // }}}
    // {{{ run()
    
    /**
     * Sets up the edit form.
     *
     * @access public
     * @return void
     */
    function run()
    {
        $this->o_form->addElement('hidden', 'objectId');
        $this->beginFormTable();
        $this->processTableHeaders();
        $this->registerSubmitRow();
    }

    // }}}
    // {{{ getSubmitButton()

    /**
     * Gets the submit button
     *
     * @access public
     * @return string The html form element
     */
    function getSubmitButton()
    {
        $this->o_form->addElement('submit', 'submit', _('Update'));
        return $this->o_form->renderElement('submit', true);
    }

    // }}}
    // {{{ setSubmitActionId()

    /**
     * Sets the actionId that should be used when the form is submitted.
     *
     * @access public 
     * @returns void
     */
    function setSubmitActionId()
    {
        $this->setActionId(ACTION_EDIT_SUBMIT);
    }

    // }}}
}
?>
