<?php
/** $Id: GenericForm.php,v 1.1 2003/02/06 18:48:10 jrust Exp $ */
// {{{ requires

require_once dirname(__FILE__) . '/GenericAction.php';
require_once 'HTML/QuickForm.php';

// }}}
// {{{ class ActionHandler_GenericForm

/**
 * The ActionHandler_GenericForm:: class has methods and properties that are needed for
 * actions that use forms. 
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
class ActionHandler_GenericForm extends ActionHandler_GenericAction {
    // {{{ properties

    /**
     * QuickForm instance 
     * @type object
     */
    var $o_form;

    /**
     * The form method (POST/GET)
     * @type string
     */
    var $formMethod = 'POST';

    /**
     * The form target
     * @type string
     */
    var $formTarget = '_self';

    /**
     * The form name
     * @type string
     */
    var $formName = 'generic_form';

    // }}}
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function ActionHandler_GenericForm()
    {
        ActionHandler_GenericAction::ActionHandler_GenericAction();
        $this->o_form =& new HTML_QuickForm($this->formName, $this->formMethod, $this->getFormAction(), $this->formTarget);
        $this->o_form->clearAllTemplates();
    }

    // }}}
    // {{{ registerSubmitRow()

    /**
     * Registers the submit row html for the form
     * 
     * @access private
     * @return void
     */
    function registerSubmitRow()
    {
        $this->o_output->touchBlock($this->tableNamespace . 'table_row');
        $this->o_output->cycleBlock($this->tableNamespace . 'table_field_cell');
        $this->o_output->assignBlockData(
            array(
                'T_table_field_cell' => $this->getSubmitButton(),
                'S_table_field_cell' => 'colspan="' . $this->numColumns . '" ' .
                                        'style="text-align: center;"',
            ),
            $this->tableNamespace . 'table_field_cell'
        );
    }

    // }}}
    // {{{ createFormElements()

    /**
     * Creates the form elements needed for the form and any rules that apply to those
     * elements. 
     *
     * @access public
     * @return void
     */
    function createFormElements()
    {
        $this->o_form->addElement('hidden', 'actionId');
        $this->o_form->addElement('submit', 'submit', _('Submit'));
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
        // interface
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
        return $this->o_form->renderElement('submit', true);
    }

    // }}}
    // {{{ getFormAction()

    /**
     * Gets the action used for the form, which is the current page.
     *
     * @access public
     * @return string The form action 
     */
    function getFormAction()
    {
        return FastFrame::selfURL();
    }

    // }}}
    // {{{ getFormConstants()

    /**
     * Gets the constants to be filled into the form
     *
     * @access public
     * @return array An array of the constants to be filled into the form
     */
    function getFormConstants()
    {
        return array('actionId' => $this->o_action->getActionId());
    }

    // }}}
}
?>
