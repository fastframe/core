<?php
/** $Id: Form.php,v 1.2 2003/02/14 23:42:49 jrust Exp $ */
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
require_once 'HTML/QuickForm.php';

// }}}
// {{{ class ActionHandler_Form

/**
 * The ActionHandler_Form:: class has methods and properties that are needed for
 * actions that use forms. 
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package ActionHandler 
 */

// }}}
class ActionHandler_Form extends ActionHandler_Action {
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
    function ActionHandler_Form()
    {
        ActionHandler_Action::ActionHandler_Action();
        $this->o_form =& new HTML_QuickForm($this->formName, $this->formMethod, $this->getFormAction(), $this->formTarget);
        $this->o_form->clearAllTemplates();
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
        $this->setSubmitActionId();
        $a_constants = $this->getFormConstants();
        $a_defaults = $this->getFormDefaults();
        // see if we encountered an error (in which case the error producing
        // method will take care of going to another action)
        if ($a_constants === false || $a_defaults === false) {
            return;
        }

        $this->o_form->setConstants($a_constants);
        $this->o_form->setDefaults($a_defaults);
        $this->createFormElements();
        $this->renderFormTable();
        $this->o_output->output($this->o_application->getMessages());
    }

    // }}}
    // {{{ renderSubmitRow()

    /**
     * Renders the submit row html for the form
     *
     * @param $in_tableObj The table object
     * 
     * @access private
     * @return void
     */
    function renderSubmitRow(&$in_tableObj)
    {
        $this->o_output->touchBlock($in_tableObj->getTableNamespace() . 'table_row');
        $this->o_output->cycleBlock($in_tableObj->getTableNamespace() . 'table_field_cell');
        $this->o_output->assignBlockData(
            array(
                'T_table_field_cell' => $this->getSubmitButtons(),
                'S_table_field_cell' => 'colspan="' . $in_tableObj->getNumColumns() . '" ' .
                                        'style="text-align: center;"',
            ),
            $in_tableObj->getTableNamespace() . 'table_field_cell'
        );
    }

    // }}}
    // {{{ renderFormTable()

    /**
     * Renders the table and form
     *
     * @access public
     * @return void
     */
    function renderFormTable()
    {
        require_once dirname(__FILE__) . '/../Output/Table.php';
        $o_table =& new FastFrame_Output_Table();
        $o_table->setTableHeaderText($this->getTableHeaderText());
        $o_table->setTableHeaders($this->getTableData());
        $o_table->renderTwoColumnTable();
        $this->o_output->assignBlockCallback(array(&$this->o_form, 'toHtml'), array(), $o_table->getTableName());
        $this->renderSubmitRow($o_table);
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
        // interface
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
        if ($this->o_action->getActionId() == ACTION_EDIT) {
            $this->o_action->setActionId(ACTION_EDIT_SUBMIT);
        }
        else {
            $this->o_action->setActionId(ACTION_ADD_SUBMIT);
        }
    }

    // }}}
    // {{{ setProblemActionId()

    /**
     * Sets the actionId that should be used when an error occurs in retrieving the data 
     *
     * @access public 
     * @returns void
     */
    function setProblemActionId()
    {
        $this->o_action->setActionId(ACTION_LIST);
    }

    // }}}
    // {{{ getSubmitButtons()

    /**
     * Returns any submit buttons rendered into html
     *
     * @access public
     * @return string The submit buttons html
     */
    function getSubmitButtons()
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
     * Gets the constant values to be filled into the forms.  Constants means it will
     * override GET/POST data with the same key. 
     *
     * @access public
     * @return mixed An array of the defaults or false if there was a problem 
     */
    function getFormConstants()
    {
        return array(
                   'actionId' => $this->o_action->getActionId(),
                   'objectId' => FastFrame::getCGIParam('objectId', 'gp'), 
                   'submit' => $this->getTableHeaderText(),
               );
    }

    // }}}
    // {{{ getFormDefaults()

    /**
     * Gets the defaults to be filled into the form by retrieving the data from the
     * application layer.  If an error occurs in getting the data we bounce to the problem
     * action id.
     *
     * @access public
     * @return mixed An array of the defaults or false if there was a problem 
     */
    function getFormDefaults()
    {
        if ($this->o_action->getActionId() == ACTION_EDIT_SUBMIT) {
            $s_id = FastFrame::getCGIParam('objectId', 'gp');
            $result = $this->o_application->getDataByPrimaryKey($s_id);
            if (!$result) {
                $this->o_output->setMessage(
                    sprintf(_('Could not find the specified %s'), $this->getSingularText()),
                    FASTFRAME_ERROR_MESSAGE
                );
                $this->setProblemActionId();
                $this->o_action->takeAction();
                return false;
            }
            else {
                return $result; 
            }
        }
        else {
            return array();
        }
    }

    // }}}
    // {{{ getTableData()

    /**
     * Gets the array that we make a basic table out of.  Has the form of 'data' => the
     * cell data, 'title' => the cell description, 'titleStyles'/'dataStyles' => any
     * attributes to apply to the title and data cells respectively.
     *
     * @access public
     * @return array
     */
    function getTableData()
    {
        // interface
    }

    // }}}
    // {{{ getTableHeaderText()
    
    /**
     * Gets the description of this form to be placed in the table header 
     *
     * @access public
     * @return string The text for the header.
     */
    function getTableHeaderText()
    {
        if ($this->o_action->getActionId() == ACTION_EDIT_SUBMIT) {
            return sprintf(_('Update %s'), $this->getSingularText());
        }
        else {
            return sprintf(_('Add %s'), $this->getSingularText());
        }
    }

    // }}}
    // {{{ getSingularText()

    /**
     * Gets the text that describes a singular version of the items displayed
     *
     * @access public
     * @return string The singular text
     */
    function getSingularText()
    {
        return _('Item');
    }

    // }}}
}
?>
