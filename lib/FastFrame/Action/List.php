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

        $this->initList();
        if (!$this->queryData()) {
            return $this->o_nextAction;
        }

        if (!FF_Request::getParam('printerFriendly', 'gp', false)) {
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

        $this->o_output->setPageName($this->getPageName());
        $this->createListTable();
        $this->setNextAction();
        return $this->o_nextAction; 
    }

    // }}}
    // {{{ initList()

    /**
     * Initializes the list class with the default parameters.
     *
     * @access public
     * @return void
     */
    function initList()
    {
        $o_actionHandler =& FF_ActionHandler::singleton();
        $this->o_list =& new FF_List(
            // a unique id for this list
            $o_actionHandler->getAppId() . $o_actionHandler->getModuleId() . $this->currentActionId,
            $this->getDefaultSortField(), 
            $this->getDefaultSortOrder(),
            $this->getDefaultDisplayLimit()
        );
        $this->o_list->setPersistentData($this->getPersistentData());
        $this->processFieldMapForList($this->getFieldMap());
    }

    // }}}
    // {{{ queryData()

    /**
     * Queries the datasource for the list data.
     *
     * @access public
     * @return bool True if successful, false otherwise 
     */
    function queryData()
    {
        list($s_filter, $a_filterData) = $this->getFilter();
        $this->o_listModeler =& new FF_ListModeler($this->o_list, $this->o_model, $s_filter, $a_filterData);
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
        $o_table->setAlternateRowColors(true);
        $o_tableWidget =& $o_table->getWidgetObject();
        // Needed for the highlight row javascript
        $o_tableWidget->assignBlockData(array('S_table' => 'id="listTable"'), 
                $this->o_output->getGlobalBlockName());
        $o_tableWidget->touchBlock('table_row');
        $o_tableWidget->cycleBlock('table_field_cell');
        $o_tableWidget->cycleBlock('table_content_cell');
        foreach ($this->o_list->generateSortFields() as $s_cell) {
            $o_tableWidget->assignBlockData(array('T_table_field_cell' => $s_cell), 'table_field_cell');
        }

        if (!FF_Request::getParam('printerFriendly', 'gp', false)) {
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

        $this->renderListData($o_table);
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
     * @param object $in_tableObj The table object 
     *
     * @access public
     * @return void
     */
    function renderListData(&$in_tableObj)
    {
        $o_tableWidget =& $in_tableObj->getWidgetObject();
        if ($this->o_list->getMatchedRecords() > 0) {
            $i = 0;
            $b_highlightRows = false;
            if (!is_null($this->getHighlightedRowUrl())) {
                $this->_renderHighlightJs();
                $b_highlightRows = true;
            }

            while ($this->o_listModeler->loadNextModel()) {
                $tmp_extraJs = '';
                if ($b_highlightRows) {
                    $tmp_extraJs = ' onclick="window.location.href=\'' . $this->getHighlightedRowUrl() . '\';"';
                }

                $o_tableWidget->assignBlockData(array('S_table_row' => 
                            'class="' . $in_tableObj->getRowClass($i++) . '"' . $tmp_extraJs), 'table_row');
                $o_tableWidget->cycleBlock('table_content_cell');
                foreach ($this->getFieldMap() as $tmp_fields) {
                    $s_attr = '';
                    $tmp_fields['args'] = isset($tmp_fields['args']) ? $tmp_fields['args'] : array();
                    // see if the method is in the model 
                    if (isset($tmp_fields['field'])) {
                        $tmp_displayData = $this->o_output->processCellData(
                                call_user_func_array(array(&$this->o_model, $tmp_fields['method']), $tmp_fields['args']));
                    }
                    // otherwise it's a method in this class
                    else {
                        $tmp_displayData = $this->o_output->processCellData(
                                call_user_func_array(array(&$this, $tmp_fields['method']), $tmp_fields['args']));
                        // Options cell has some special attributes
                        if ($tmp_fields['method'] = 'getOptions') {
                            $s_attr = 'id="optionCell' . $i . '" style="width: 5%; white-space: nowrap;"';
                        }
                    }

                    $o_tableWidget->assignBlockData(array(
                                'T_table_content_cell' => $tmp_displayData,
                                'S_table_content_cell' => $s_attr), 'table_content_cell');
                }
            }
        }
        else {
            $o_tableWidget->assignBlockData(array('S_table_row' => 'class="primaryRow"'), 'table_row');
            $o_tableWidget->cycleBlock('table_content_cell');
            $o_tableWidget->assignBlockData(
                array(
                    'T_table_content_cell' => $this->getNoResultText(),
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
    // {{{ getHighlightedRowUrl()

    /**
     * Gets the url to go to if the user clicks on the highlighted row
     * (the row becomes highlighted when they go over it).  If nothing
     * is returned then row highlighting is not turned on.
     *
     * @access public
     * @return string The url to go to for the highlighted row.
     */
    function getHighlightedRowUrl()
    {
        // interface
    }

    // }}}
    // {{{ getFilter()

    /**
     * Gets the filter for the list and any associated data or flags that go with that
     * filter.  Default is to have no filter on the list.
     *
     * @access public
     * @return array First element is filter name, second element is an array of extra data
     */
    function getFilter()
    {
        return array(null, array());
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
     * to run to get the data for that field.  Available keys are:
     * 'method' => Name of method to call.  If field name is empty then
     *   the method is assumed to be part of this object (instead of the
     *   model object)
     * 'args' => Optional array of args to be passed to method
     * 'field' => Field name to search & sort by.  If false then field
     *   is not searchable/sortable.  Can also be empty.
     * 'description' => Description of the field.
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
    // {{{ getNoResultText()

    /**
     * Returns the text for when there no results were returned.
     *
     * @access public
     * @return string
     */
    function getNoResultText()
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
    // {{{ _renderHighlightJs()

    /**
     * Renders the highlight row javascript.
     *
     * @access private
     * @return void
     */
    function _renderHighlightJs()
    {
        $this->o_output->assignBlockData(
            array('T_javascript' => '<script language="JavaScript" type="text/javascript" src="' . 
                $this->o_registry->getRootFile('highlightRow.js', 'javascript', FASTFRAME_WEBPATH) . 
                '"></script>' . "\n"),
            'javascript');
    }

    // }}}
}
?>
