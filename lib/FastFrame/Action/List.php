<?php
/** $Id: List.php,v 1.8 2003/03/15 01:26:57 jrust Exp $ */
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
require_once dirname(__FILE__) . '/../Model/ListModeler.php';
require_once dirname(__FILE__) . '/../Output/Table.php';

// }}}
// {{{ class FF_Action_List

/**
 * The FF_Action_List:: class creates a list of items by using the FastFrame_List class
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
     * @type object 
     */
    var $o_list;

    /**
     * The list helper object
     * @type object
     */
    var $o_listModeler;

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
    function FF_Action_List(&$in_model)
    {
        FF_Action_Form::FF_Action_Form($in_model);
    }

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
        $this->o_output->setPageName($this->getPageName());
        $this->initList();
        $this->renderAdditionalLinks();
        $this->o_list->renderSearchBox($this->getSingularText(), $this->getPluralText());
        $this->createListTable();
        $this->o_output->output();
        return $this->o_nextAction; 
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
        $this->o_list =& new FastFrame_List(
            $this->getDefaultSortField(), 
            $this->getDefaultSortOrder(),
            $this->getDefaultDisplayLimit(),
            array('actionId' => $this->currentActionId)
        );
        $this->processFieldMapForList($this->getFieldMap());
        $this->o_listModeler =& new FF_Model_ListModeler($this->o_list, $this->o_model);
        $this->o_list->setTotalRecords($this->o_listModeler->getTotalModelsCount());
        $this->o_list->setMatchedRecords($this->o_listModeler->getMatchedModelsCount());
        $this->o_list->setDisplayedRecords($this->o_listModeler->getDisplayedModelsCount());
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
        $this->renderListData($o_table->getTableNamespace());
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
     * @param string $in_namespace The table namespace 
     *
     * @access public
     * @return void
     */
    function renderListData($in_namespace)
    {
        if ($this->o_list->getDisplayedRecords() > 0) {
            while (($this->o_model =& $this->o_listModeler->getNextModel()) !== false) {
                $this->o_output->touchBlock($in_namespace . 'table_row');
                $this->o_output->cycleBlock($in_namespace . 'table_content_cell');
                foreach ($this->getFieldMap() as $tmp_fields) {
                    // see if the method refers to a field in the model
                    if (isset($tmp_fields['field'])) {
                        $tmp_displayData = $this->o_output->processCellData($this->o_model->$tmp_fields['method']());
                    }
                    // otherwise it's a model in this class
                    else {
                        $tmp_displayData = $this->o_output->processCellData($this->$tmp_fields['method']());
                    }

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
        return 30;
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
     * model.  If no field is supplied then we will assume the cell will not contain data
     * from the database, but a method still needs to be supplied (which exists in this
     * object, since it has nothing to do with the model) and which will be used to fill
     * in the data for that cell.  An example element in the array would look like:
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
