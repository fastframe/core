<?php
/** $Id: GenericForm.php,v 1.3 2003/02/08 00:10:55 jrust Exp $ */
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

require_once dirname(__FILE__) . '/GenericAction.php';
require_once 'HTML/QuickForm.php';

// }}}
// {{{ class ActionHandler_GenericForm

/**
 * The ActionHandler_GenericForm:: class has methods and properties that are needed for
 * actions that use forms. 
 *
 * @author  Jason Rust <jrust@codejanitor.com>
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
     * @param $in_tableObj The table object
     * 
     * @access private
     * @return void
     */
    function registerSubmitRow(&$in_tableObj)
    {
        $this->o_output->touchBlock($in_tableObj->getTableNamespace() . 'table_row');
        $this->o_output->cycleBlock($in_tableObj->getTableNamespace() . 'table_field_cell');
        $this->o_output->assignBlockData(
            array(
                'T_table_field_cell' => $this->getSubmitButton(),
                'S_table_field_cell' => 'colspan="' . $in_tableObj->getNumColumns() . '" ' .
                                        'style="text-align: center;"',
            ),
            $in_tableObj->getTableNamespace() . 'table_field_cell'
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
