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

require_once dirname(__FILE__) . '/Form.php';
require_once dirname(__FILE__) . '/../List.php';
require_once dirname(__FILE__) . '/../ListModeler.php';
require_once dirname(__FILE__) . '/../Output/Table.php';

// }}}
// {{{ class FF_Action_List

/**
 * The FF_Action_List:: class creates a list of items by using the FF_List class
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package ActionHandler 
 */

// }}}
class FF_Action_List extends FF_Action_Form {
    // {{{ properties

    /**
     * The list object 
     * @var object 
     */
    var $o_list;

    /**
     * The list helper object
     * @var object
     */
    var $o_listModeler;

    // }}}
    // {{{ run()
    
    /**
     * Lists the object from the database 
     *
     * @access public
     * @return object The next action object
     */
    function run()
    {
        if (!$this->checkPerms()) {
            return $this->o_nextAction;
        }

        $this->o_output->setPageName($this->getPageName());
        if (!$this->initList()) {
            return $this->o_nextAction;
        }

        if (!FastFrame::getCGIParam('printerFriendly', 'gp', false)) {
            $this->renderAdditionalLinks();
            $this->o_output->assignBlockData(
                    array('W_search_box' => $this->o_list->renderSearchBox(
                            $this->getSingularText(), $this->getPluralText())),
                    'switch_search_box');
        }
        else {
            $s_link = $this->o_output->link(
                    FastFrame::selfURL($this->o_list->getAllListVariables()), 
                    _('Return To List'));
            $this->o_output->assignBlockData(array('T_page_link' => $s_link), 'page_link');
        }

        $this->createListTable();
        $this->setNextAction();
        return $this->o_nextAction; 
    }

    // }}}
    // {{{ initList()

    /**
     * Initializes the list class
     *
     * @access public
     * @return bool True if successful, false otherwise 
     */
    function initList()
    {
        $this->o_list =& new FF_List(
            $this->getDefaultSortField(), 
            $this->getDefaultSortOrder(),
            $this->getDefaultDisplayLimit(),
            $this->getPersistentData()
        );
        $this->processFieldMapForList($this->getFieldMap());
        $this->o_listModeler =& new FF_ListModeler($this->o_list, $this->o_model, $this->getFilter());
        if (!$this->o_listModeler->performSearch()) {
            $this->o_output->setMessage(_('Unable to query data'), FASTFRAME_ERROR_MESSAGE);
            $this->o_nextAction->setNextActionId(ACTION_PROBLEM);
            return false;
        }

        $this->o_list->setTotalRecords($this->o_listModeler->getTotalModelsCount());
        $this->o_list->setMatchedRecords($this->o_listModeler->getMatchedModelsCount());
        return true;
    }

    // }}}
    // {{{ createListTable()

    /**
     * Creates the list table and fills in the data
     *
     * @access public
     * @return void
     */
    function createListTable()
    {
        $o_table =& new FF_Output_Table();
        $o_table->setTableHeaderText($this->getTableHeaderText());
        $o_table->setNumColumns(count($this->o_list->getColumnData()));
        $o_table->beginTable();
        $o_tableWidget =& $o_table->getWidgetObject();
        $o_tableWidget->touchBlock('table_row');
        $o_tableWidget->cycleBlock('table_field_cell');
        $o_tableWidget->cycleBlock('table_content_cell');
        foreach ($this->o_list->generateSortFields() as $s_cell) {
            $o_tableWidget->assignBlockData(array('T_table_field_cell' => $s_cell), 'table_field_cell');
        }

        if (!FastFrame::getCGIParam('printerFriendly', 'gp', false)) {
            $a_data = $this->o_list->generateNavigationLinks();
            $o_navWidget =& $this->o_output->getWidgetObject('navigationRow');
            $o_navWidget->assignBlockData(array(
                    'S_TABLE_COLUMNS'       => $o_table->getNumColumns(),
                    'I_navigation_first'    => $a_data['first'],
                    'I_navigation_previous' => $a_data['previous'],
                    'I_navigation_next'     => $a_data['next'],
                    'I_navigation_last'     => $a_data['last'],
                    ));
            $o_tableWidget->assignBlockData(array('T_end_data' => $o_navWidget->render()), $this->o_output->getGlobalBlockName());
        }

        $this->renderListData($o_tableWidget);
        $this->o_output->assignBlockData(array('W_content_middle' => $o_tableWidget->render()), 'content_middle');
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
    // {{{ renderListData()

    /**
     * Registers the data for the list into the table
     *
     * @param object $in_tableWidget The table widget 
     *
     * @access public
     * @return void
     */
    function renderListData(&$in_tableWidget)
    {
        if ($this->o_list->getMatchedRecords() > 0) {
            while ($this->o_listModeler->loadNextModel()) {
                $in_tableWidget->touchBlock('table_row');
                $in_tableWidget->cycleBlock('table_content_cell');
                foreach ($this->getFieldMap() as $tmp_fields) {
                    // see if the method is in the model 
                    if (isset($tmp_fields['field'])) {
                        $tmp_displayData = $this->o_output->processCellData($this->o_model->$tmp_fields['method']());
                    }
                    // otherwise it's a method in this class
                    else {
                        $tmp_displayData = $this->o_output->processCellData($this->$tmp_fields['method']());
                    }

                    $in_tableWidget->assignBlockData(array('T_table_content_cell' => $tmp_displayData), 'table_content_cell');
                }
            }
        }
        else {
            $in_tableWidget->touchBlock('table_row');
            $in_tableWidget->cycleBlock('table_content_cell');
            $in_tableWidget->assignBlockData(
                array(
                    'T_table_content_cell' => $this->getEmptySetText(),
                    'S_table_content_cell' => 'style="font-style: italic; text-align: center;" colspan="' . count($this->o_list->getColumnData()) . '"', 
                ),
                'table_content_cell'
            );
        }
    }

    // }}}
    // {{{ processFieldMapForList()

    /**
     * Process the field map configuration used by FastFrame into the columnData and
     * searchableFields properties of the list object. 
     *
     * @param bool $in_addAllFields (optional) Add the All Fields param to the beginning of the
     *                              searchable fields list? 
     *
     * @access public
     * @return void 
     */
    function processFieldMapForList($in_addAllFields = true)
    {
        $a_colData = array();
        foreach ($this->getFieldMap() as $a_val) {
            if (isset($a_val['field']) && $a_val['field'] !== false) {
                $a_colData[] = array('sort' => $a_val['field'], 'name' => $a_val['description']);
            }
            // not a sortable column
            else {
                $a_colData[] = array('name' => $a_val['description']);
            }
        }
        
        $this->o_list->setColumnData($a_colData);
        $this->o_list->setSearchableFields($a_colData, $in_addAllFields);
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
    // {{{ getPersistentData()

    /**
     * Gets an array of the persistent data that will be passed from page to page on the
     * list.
     *
     * @access public
     * @return array An array of the name => value pairs of persistent data
     */
    function getPersistentData()
    {
        return array('actionId' => $this->currentActionId);
    }

    // }}}
    // {{{ getFilter()

    /**
     * Gets the filter for the list.  Default is to have no filter on the list.
     *
     * @access public
     * @return string The name of the filter to apply to the list
     */
    function getFilter()
    {
        // interface
    }

    // }}}
    // {{{ getDefaultSortField()
    
    /**
     * Returns the default sort field
     *
     * @access public
     * @return string The field name for the default sort
     */
    function getDefaultSortField()
    {
        // interface
    }

    // }}}
    // {{{ getDefaultDisplayLimit()
    
    /**
     * Returns the default display limit for the list page 
     *
     * @access public
     * @return int The display limit 
     */
    function getDefaultDisplayLimit()
    {
        return 20;
    }

    // }}}
    // {{{ getDefaultSortOrder()
    
    /**
     * Returns the default sort order for the list in the form of an integer (0 = DESC, 1 =
     * ASC)
     *
     * @access public
     * @return int The sort order 
     */
    function getDefaultSortOrder()
    {
        return 1;
    }

    // }}}
    //{{{ getFieldMap()

    /**
     * Returns the field map array.
     *
     * The map of fields to display in the list page, their description, and the method
     * (that exists in the model object) to run to get the data for that field from the
     * model.  If field is false then that means the row should not be sortable but the
     * method exists as part of the model class.  If no field is supplied then it means the
     * method is part of this object. An example element in the array would look like:
     * array('field' => 'username', 'description' => _('User Name'), 'method' => 'getUserName')
     *
     * @access public
     * @return array
     */
    function getFieldMap()
    {
        // interface
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
    // {{{ getPluralText()

    /**
     * Gets the text that describes a plural version of the items displayed
     *
     * @access public
     * @return string The plural text
     */
    function getPluralText()
    {
        return _('Items');
    }

    // }}}
    // {{{ getEmptySetText()

    /**
     * Returns the text for when there is an empty data set
     *
     * @access public
     * @return string
     */
    function getEmptySetText()
    {
        return sprintf(_('No %s were found.'), strtolower($this->getPluralText()));
    }
    
    // }}}
    // {{{ getTableHeaderText()

    /**
     * Gets the description of the table 
     *
     * @access public
     * @return string The text for the header of the table 
     */
    function getTableHeaderText()
    {
        return sprintf(_('List of %s'), $this->getPluralText());
    }

    // }}}
    // {{{ setNextAction()

    /**
     * Sets the next action for the list.
     *
     * @access public
     * @return void
     */
    function setNextAction()
    {
        // normally this is the last action
    }

    // }}}
}
?>
