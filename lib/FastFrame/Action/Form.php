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
require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/Renderer/QuickHtml.php';

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
     * @var object
     */
    var $o_form;

    /**
     * The form renderer instance
     * @var object
     */
    var $o_renderer;

    /**
     * The model object that holds info about the current model being worked with
     * @var object
     */
    var $o_model;

    /**
     * The form method (POST/GET)
     * @var string
     */
    var $formMethod = 'POST';

    /**
     * The form target
     * @var string
     */
    var $formTarget = '_self';

    /**
     * The form name
     * @var string
     */
    var $formName = 'generic_form';

    /**
     * The action id for when the form is submitted
     * @var string
     */
    var $formActionId;

    /**
     * The action id used in edit mode
     * @var string
     */
    var $editActionId = ACTION_EDIT;

    // }}}
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @param object $in_model The model object
     *
     * @access public
     * @return void
     */
    function FF_Action_Form(&$in_model)
    {
        FF_Action::FF_Action($in_model);
        $this->o_form =& new HTML_QuickForm($this->formName, $this->formMethod, $this->getFormAction(), $this->formTarget);
        $this->o_renderer =& new HTML_QuickForm_Renderer_QuickHtml();
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
        if (!$this->checkPerms()) {
            return $this->o_nextAction;
        }

        $this->setSubmitActionId();
        $b_result = $this->fillModelWithData();
        // see if we encountered an error.
        if (!$b_result) {
            return $this->o_nextAction;
        }

        $this->o_output->setPageName($this->getPageName());
        $this->renderAdditionalLinks();
        $this->o_form->setConstants($this->getFormConstants());
        $this->o_form->setDefaults($this->getFormDefaults());
        $this->createFormElements();
        $this->o_form->accept($this->o_renderer);
        $o_table =& $this->renderFormTable();
        $o_tableWidget =& $o_table->getWidgetObject();
        $this->o_output->assignBlockData(array('W_content_middle' => $o_tableWidget->render()), 'content_middle');
        return $this->o_nextAction;
    }

    // }}}
    // {{{ renderAdditionalLinks()

    /**
     * Renders additional links on the page
     *
     * @access public
     * @return void
     */
    function renderAdditionalLinks()
    {
        // interface
    }

    // }}}
    // {{{ renderSubmitRow()

    /**
     * Renders the submit row html for the form
     *
     * @param object $in_tableWidget The table widget 
     * @param int $in_colspan The number of columns to span 
     * 
     * @access private
     * @return void
     */
    function renderSubmitRow(&$in_tableWidget, $in_colspan)
    {
        $in_tableWidget->touchBlock('table_row');
        $in_tableWidget->cycleBlock('table_field_cell');
        $in_tableWidget->assignBlockData(
            array(
                'T_table_field_cell' => $this->getSubmitButtons(),
                'S_table_field_cell' => 'colspan="' . $in_colspan . '" style="text-align: center;"',
            ),
            'table_field_cell'
        );
    }

    // }}}
    // {{{ renderFormTable()

    /**
     * Renders the table and form using the genericTable widget.
     *
     * @access public
     * @return object The table object 
     */
    function &renderFormTable()
    {
        require_once dirname(__FILE__) . '/../Output/Table.php';
        $o_table =& new FF_Output_Table();
        $o_table->setTableHeaderText($this->getTableHeaderText());
        $o_table->setTableHeaders($this->getTableData());
        $o_table->renderTwoColumnTable();
        $o_tableWidget =& $o_table->getWidgetObject();
        $this->renderSubmitRow($o_tableWidget, $o_table->getNumColumns());
        $o_tableWidget->assignBlockCallback(array(&$this->o_renderer, 'toHtml'));
        return $o_table;
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
    // {{{ fillModelWithData()

    /**
     * Fills the model object with data for the field being edited.  If an error occurs in
     * getting the data we bounce to the problem action id.
     *
     * @access public
     * @return bool True if the model loaded successfully, false otherwsie
     */
    function fillModelWithData()
    {
        if ($this->isUpdate()) {
            $s_id = FF_Request::getParam('objectId', 'pg');
            $this->o_model->reset();
            if (!$this->o_model->fillById($s_id)) {
                $this->o_output->setMessage(
                    sprintf(_('Could not find the specified %s'), $this->getSingularText()),
                    FASTFRAME_ERROR_MESSAGE
                );
                $this->setProblemActionId();
                return false;
            }
        }

        return true;
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
    // {{{ setSubmitActionId()

    /**
     * Sets the actionId that should be used when the form is submitted.
     *
     * @access public 
     * @returns void
     */
    function setSubmitActionId()
    {
        if ($this->isUpdate()) {
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
        return $this->o_renderer->elementToHtml('submitbutton');
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
                   'objectId' => FF_Request::getParam('objectId', 'pg'), 
                   'submitbutton' => $this->getTableHeaderText(),
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
        return array();
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
        return array();
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
        if ($this->isUpdate()) {
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
    // {{{ getPageName()

    /**
     * Returns the page name for this action 
     *
     * @access public
     * @return string The page name
     */
    function getPageName()
    {
        return $this->getTableHeaderText();
    }

    // }}}
}
?>
