<?php
/** $Id: List.php,v 1.3 2003/02/08 00:10:55 jrust Exp $ */
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

require_once dirname(__FILE__) . '/GenericForm.php';
require_once dirname(__FILE__) . '/../List.php';
require_once dirname(__FILE__) . '/../Output/Table.php';

// }}}
// {{{ class ActionHandler_List

/**
 * The ActionHandler_List:: class creates a list of items by using the FastFrame_List class
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package ActionHandler 
 */

// }}}
class ActionHandler_List extends ActionHandler_GenericForm {
    // {{{ properties

    /**
     * The list object 
     * @type object 
     */
    var $o_list;

    /**
     * Array of the data to be filled into the list
     * @type array
     */
    var $dataArray = array();

    /**
     * The field map array used by FastFrame for list configuration
     * @type array
     */
    var $fieldMap = array();

    // }}}
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function ActionHandler_List()
    {
        ActionHandler_GenericForm::ActionHandler_GenericForm();
    }

    // }}}
    // {{{ run()
    
    /**
     * Lists the object from the database 
     *
     * @access public
     * @return void
     */
    function run()
    {
        $this->o_output->setPageName($this->getPageName());
        $this->initList();
        $this->registerAdditionalLinks();
        $this->o_list->registerSearchBox($this->getSingularText(), $this->getPluralText());
        $this->createListTable();
        $this->o_output->output();
    }

    // }}}
    // {{{ initList()

    /**
     * Initializes the list class
     *
     * @access public
     * @return void
     */
    function initList()
    {
        $this->o_list =& new FastFrame_List();
        $this->o_application->setListObject($this->o_list);
        $this->fieldMap = $this->o_registry->getConfigParam('app/field_map');
        $this->processFieldMap($this->fieldMap);
        $this->o_list->setTotalRecords($this->o_application->getListTotalRecordsCount());
        $this->dataArray = $this->o_application->getListData();
        if (PEAR::isError($this->dataArray)) {
            $this->o_output->setMessage($a_data->getMessage(), FASTFRAME_ERROR_MESSAGE);
            $this->dataArray = array();
            $this->o_list->setTotalRecords(0);
        }
        else {
            $this->o_list->setMatchedRecords($this->o_application->getListMatchedRecordsCount());
            $this->o_list->setDisplayedRecords(count($this->dataArray));
        }
    }

    // }}}
    // {{{ registerAdditionalLinks()

    /**
     * Registers additional links on the page
     *
     * @access public
     * @return void
     */
    function registerAdditionalLinks()
    {
        // interface
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
        $o_table =& new FastFrame_Output_Table();
        $o_table->setTableHeaderText($this->getTableHeaderText());
        $o_table->setNumColumns(count($this->o_list->getColumnData()));
        $o_table->beginTable();
        $this->o_list->generateSortFields($o_table->getTableNamespace());
        $this->o_list->generateNavigationLinks();
        $this->o_output->touchBlock($o_table->getTableNamespace() . 'switch_table_navigation');
        $this->registerListData($o_table->getTableNamespace());
    }
    
    // }}}
    // {{{ registerListData()

    /**
     * Registers the data for the list into the table
     *
     * @param string $in_namespace The table namespace 
     *
     * @access public
     * @return void
     */
    function registerListData($in_namespace)
    {
        if ($this->o_list->getDisplayedRecords() > 0) {
            foreach ($this->dataArray as $tmp_data) {
                $this->o_output->touchBlock($in_namespace . 'table_row');
                $this->o_output->cycleBlock($in_namespace . 'table_content_cell');
                foreach ($this->fieldMap as $tmp_fields) {
                    $tmp_displayData = $this->o_output->processCellData($this->getCellData($tmp_data, $tmp_fields));
                    $this->o_output->assignBlockData(
                        array(
                            'T_table_content_cell' => $tmp_displayData,
                        ),
                        $in_namespace . 'table_content_cell'
                    );
                }
            }
        }
        else {
            $this->o_output->touchBlock($in_namespace . 'table_row');
            $this->o_output->cycleBlock($in_namespace . 'table_content_cell');
            $this->o_output->assignBlockData(
                array(
                    'T_table_content_cell' => $this->getEmptySetText(),
                    'S_table_content_cell' => 'style="font-style: italic; text-align: center;" colspan="' . count($this->o_list->getColumnData()) . '"', 
                ),
                $in_namespace . 'table_content_cell'
            );
        }
    }

    // }}}
    // {{{ processFieldMap()

    /**
     * Process the field map configuration used by FastFrame into the columnData and
     * searchableFields properties of the list object. 
     *
     * @param array $in_fieldMap The field map used by FastFrame apps.
     * @param bool $in_addAllFields (optional) Add the All Fields param to the beginning of the
     *                              searchable fields list? 
     *
     * @access public
     * @return void 
     */
    function processFieldMap($in_fieldMap, $in_addAllFields = true)
    {
        $a_colData = array();
        foreach ($in_fieldMap as $a_val) {
            if (isset($a_val['field'])){
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
    // {{{ getCellData()

    /**
     * Gets the cell data using the database connection and the field map.
     *
     * @param object $in_data The data array 
     * @param array $in_fieldMap The field map for the field we want 
     *
     * @access public
     * @return string The text to put in the cell in the list.
     */
    function getCellData($in_data, $in_fieldMap)
    {
        if (isset($in_fieldMap['method']) && 
            method_exists($this->o_application, $in_fieldMap['method'])) {
            if (isset($in_data[$in_fieldMap['field']])) {
                $s_cellData = $this->o_application->$in_fieldMap['method']($in_data[$in_fieldMap['field']]);
            }
            else {
                $s_cellData = $this->o_application->$in_fieldMap['method']();
            }
        }
        else {
            if (!isset($in_data[$in_fieldMap['field']])) {
                $s_cellData = sprintf(_('Warning: field property for %s is not set!'), $in_fieldMap['description']);
            }
            else {
                $s_cellData = $in_data[$in_fieldMap['field']];
            }
        }

        return $s_cellData;
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
        return _('List');
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
        return _('No items were found.');
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
}
?>
