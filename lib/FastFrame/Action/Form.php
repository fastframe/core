<?php
/** $Id: Form.php,v 1.3 2003/02/22 02:08:24 jrust Exp $ */
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
require_once 'HTML/QuickForm.php';

// }}}
// {{{ class FF_Action_Form

/**
 * The FF_Action_Form:: class has methods and properties that are needed for
 * actions that use forms. 
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package ActionHandler 
 */

// }}}
class FF_Action_Form extends FF_Action {
    // {{{ properties

    /**
     * QuickForm instance 
     * @type object
     */
    var $o_form;

    /**
     * The model object that holds info about the current model being worked with
     * @type object
     */
    var $o_model;

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

    /**
     * The action id for when the form is submitted
     * @type string
     */
    var $formActionId;

    // }}}
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function FF_Action_Form()
    {
        FF_Action::FF_Action();
        $this->o_form =& new HTML_QuickForm($this->formName, $this->formMethod, $this->getFormAction(), $this->formTarget);
        $this->o_form->clearAllTemplates();
    }

    // }}}
    // {{{ run()
    
    /**
     * Sets up the edit form.
     *
     * @access public
     * @return object The next action object
     */
    function run()
    {
        $this->setSubmitActionId();
        $b_result = $this->loadModel();
        // see if we encountered an error.
        if (!$b_result) {
            return $this->o_nextAction;
        }

        $this->o_form->setConstants($this->getFormConstants());
        $this->o_form->setDefaults($this->getFormDefaults());
        $this->createFormElements();
        $this->renderFormTable();
        $this->o_output->output();
        return $this->o_nextAction;
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
    // {{{ loadModel()

    /**
     * Loads the model object with data for the field being edited.  If an error occurs in
     * getting the data we bounce to the problem action id.
     *
     * @access public
     * @return bool True if the model loaded successfully, false otherwsie
     */
    function loadModel()
    {
        if ($this->currentActionId == ACTION_EDIT) {
            $s_id = FastFrame::getCGIParam('objectId', 'gp');
            $this->o_model =& $this->o_dataAccess->getModelByPrimaryKey($s_id);
            if (!$this->o_model) {
                $this->o_output->setMessage(
                    sprintf(_('Could not find the specified %s'), $this->getSingularText()),
                    FASTFRAME_ERROR_MESSAGE
                );
                $this->setProblemActionId();
                return false;
            }
        }
        else {
            $this->o_model =& $this->o_dataAccess->getNewModel();
        }

        return true;
    }

    // }}}
    // {{{ modelToFormFields()

    /**
     * Converts the model to the appropriate form fields.  Returns an array of 'field' =>
     * 'value'
     *
     * @param $in_model The model to convert
     *
     * @access public
     * @return array The array of fields and values
     */
    function modelToFormFields(&$in_model)
    {
        return array();
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
        if ($this->currentActionId == ACTION_EDIT) {
            $this->formActionId = ACTION_EDIT_SUBMIT;
        }
        else {
            $this->formActionId = ACTION_ADD_SUBMIT;
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
        $this->o_nextAction->setNextActionId(ACTION_LIST);
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
                   'actionId' => $this->formActionId,
                   'objectId' => FastFrame::getCGIParam('objectId', 'gp'), 
                   'submit' => $this->getTableHeaderText(),
               );
    }

    // }}}
    // {{{ getFormDefaults()

    /**
     * Gets the defaults to be filled into the form by retrieving the data from the
     * model. 
     *
     * @access public
     * @return array An array of the defaults
     */
    function getFormDefaults()
    {
        if ($this->currentActionId == ACTION_EDIT) {
            return $this->modelToFormFields($this->o_model); 
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
        if ($this->currentActionId == ACTION_EDIT) {
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
