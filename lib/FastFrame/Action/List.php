<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2004 The Codejanitor Group                        |
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

        $this->renderAdditionalLinks();
        $this->o_output->o_tpl->assign(array('has_search_box' => true, 
                    'W_search_box' => $this->o_list->renderSearchBox($this->getPluralText())));

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
        list($a_colData, $a_searchData) = $this->processFieldMapForList();
        $this->o_list->setColumnData($a_colData);
        $this->o_list->setSearchableFields($a_searchData, true);
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
        $s_numCols = count($this->o_list->getColumnData());
        $o_tableWidget =& new FF_Smarty('multiColumnTable');
        $o_tableWidget->assign(array('has_table_header' => true, 'has_field_row' => true,
                    // Needed for the highlight row javascript
                    'S_table' => 'id="listTable"',
                    'T_table_header' => $this->getTableHeaderText(),
                    'S_table_columns' => $s_numCols)); 
        foreach ($this->o_list->generateSortFields() as $s_cell) {
            $o_tableWidget->append('fieldCells', array('T_table_field_cell' => $s_cell));
        }

        $a_data = $this->o_list->generateNavigationLinks();
        $o_tableWidget->assign(array('S_table_columns' => $s_numCols, 
                    'I_navigation_first' => $a_data['first'],
                    'I_navigation_previous' => $a_data['previous'],
                    'I_navigation_next' => $a_data['next'],
                    'I_navigation_last' => $a_data['last']));

        $this->renderListData($o_tableWidget, $s_numCols);
        $this->o_output->o_tpl->append('content_middle', $o_tableWidget->fetch());
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
     * @param int $in_numCols The number of columns in the table
     *
     * @access public
     * @return void
     */
    function renderListData(&$in_tableWidget, $in_numCols)
    {
        if ($this->o_list->getMatchedRecords() > 0) {
            $i = 0;
            $b_highlightRows = false;
            if (!is_null($this->getHighlightedRowUrl())) {
                $this->_renderHighlightJs();
                $b_highlightRows = true;
            }
            $a_buttonCells = $this->getButtonCells();
            $a_map = $this->getFieldMap();
            while ($this->o_listModeler->loadNextModel()) {
                $tmp_extraJs = $b_highlightRows ? 
                    ' onclick="window.location.href=\'' . $this->getHighlightedRowUrl() . '\';"' : '';
                $a_cells = array();
                foreach ($a_map as $tmp_fields) {
                    $s_attr = isset($tmp_fields['attr']) ? $tmp_fields['attr'] : '';
                    $tmp_fields['args'] = isset($tmp_fields['args']) ? $tmp_fields['args'] : array();
                    // Cells with buttons have  special attributes
                    foreach ($a_buttonCells as $s_method ) {
                        if ($tmp_fields['method'] == $s_method ) {
                            $s_attr = 'id="optionCell' . $i . '" style="white-space: nowrap;"';
                            $tmp_fields['object'] =& $this;
                        }
                    }
                    

                    if (isset($tmp_fields['object'])) {
                        $tmp_displayData = $this->o_output->processCellData(
                                call_user_func_array(array(&$tmp_fields['object'], $tmp_fields['method']), $tmp_fields['args']));
                    }
                    // Otherwise use the model object
                    else {
                        $tmp_displayData = $this->o_output->processCellData(
                                call_user_func_array(array(&$this->o_model, $tmp_fields['method']), $tmp_fields['args']));
                    }

                    $a_cells[] = array('T_table_content_cell' => $tmp_displayData, 'S_table_content_cell' => $s_attr);
                }

                $in_tableWidget->append('rows', array(
                            'S_table_row' => 'class="' . $this->o_output->toggleRow($i++) . '" ' .
                                $this->getRowAttributes() . $tmp_extraJs,
                            'cells' => $a_cells));
            }
        }
        else {
            $in_tableWidget->append('rows', array('S_table_row' => 'class="primaryRow"',
                        'cells' => array(array('T_table_content_cell' => $this->getNoResultText(),
                            'S_table_content_cell' => 'style="font-style: italic; text-align: center;" colspan="' . $in_numCols . '"'))));
        }
    }

    // }}}
    // {{{ processFieldMapForList()

    /**
     * Process the field map configuration used by FastFrame into the
     * columnData and searchableFields properties of the list object. 
     *
     * @access public
     * @return array An array of 0 => colData, 1 => searchData 
     */
    function processFieldMapForList()
    {
        $a_colData = array();
        $a_searchData = array();
        foreach ($this->getFieldMap() as $s_key => $a_val) {
            $a_colData[$s_key] = array('name' => $a_val['description']);
            $a_searchData[$s_key] = array('name' => $a_val['description']);

            if (isset($a_val['field'])) {
                $a_colData[$s_key]['sort'] = $a_val['field'];
                $a_searchData[$s_key]['search'] = $a_val['field'];
            }

            if (isset($a_val['search'])) {
                $a_searchData[$s_key]['search'] = $a_val['search'];
            }

            if (isset($a_val['sort'])) {
                $a_colData[$s_key]['sort'] = $a_val['sort'];
            }
        }
        
        return array($a_colData, $a_searchData);
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
    // {{{ getRowAttributes()

    /**
     * Gets any extra style or javascript attributes for the current
     * row.
     *
     * @access public
     * @return string The style or javascript attribute.
     */
    function getRowAttributes()
    {
        // interface
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
    // {{{ getButtonCells()
    
    /**
     * Gets an array of cells that have clickable buttons in them
     *
     * @access public
     * @return array An array of values with the method names of cells that will render buttons
     */
    
    function getButtonCells()
    {
        return array('getOptions');
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
     * 'object' => The object used to call the method.  Default is
     *  $this->o_model.
     * 'method' => Name of method to call.
     * 'args' => Optional array of args to be passed to method
     * 'search' => Field name to search by.
     * 'sort' => Field name to sort by.
     * 'field' => A shortcut way to set a field as both searchable and
     *  sortable.
     *  'attr' => Any attributes to apply to the cell
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
        $this->o_output->addScriptFile($this->o_registry->getRootFile('highlightRow.js', 'javascript', FASTFRAME_WEBPATH));
    }

    // }}}
}
?>
