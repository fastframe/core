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
// {{{ class FF_ListModeler

/**
 * The FF_ListModeler:: is a class to help in producing a list of models.
 *
 * This class provides the ability to get the necessary data from a SQL database and return
 * a new model upon each iteration of the result set.  Is used in conjunction with an
 * existing model object and list object.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package List 
 */

// }}}
class FF_ListModeler {
    // {{{ properties

    /**
     * The List object 
     * @var object 
     */
    var $o_list;

    /**
     * The model object
     * @var object
     */
    var $o_model;

    /**
     * The data access object 
     * @var object 
     */
    var $o_dataAccess;

    /**
     * The result set object
     * @var object
     */
    var $o_resultSet = null;

    /**
     * The filter to apply to the data
     * @var string
     */
    var $filterName = null;

    /**
     * The associated data or flags that go with the filter
     * @var array
     */
    var $filterData = array();

    /**
     * The current row count
     * @var int
     */
    var $currentRow;

    /**
     * The ending row for the list
     * @var int
     */
    var $endRow;

    // }}}
    // {{{ constructor

    /**
     * Initialize any needed class variables
     *
     * @param object $in_listObject The list object
     * @param object $in_modelObject The model object
     * @param string $in_filter The name of the filter to apply to the list
     * @param array $in_filterData Any extra data or flags that goes with the filter
     *
     * @access public
     * @return void
     */
    function FF_ListModeler(&$in_listObject, &$in_modelObject, $in_filter, $in_filterData)
    {
        $this->o_list =& $in_listObject;
        $this->o_model =& $in_modelObject;
        $this->o_dataAccess =& $this->o_model->getDataAccessObject();
        $this->filterName = $in_filter;
        $this->filterData = $in_filterData;
        $this->currentRow = $this->o_list->getRecordOffset();
        $this->endRow = $this->currentRow + $this->o_list->getDisplayLimit();
    }

    // }}}
    // {{{ getTotalModelsCount()

    /**
     * Gets the count of total number of models in the data set
     *
     * @access public
     * @return int The total number of models.
     */
    function getTotalModelsCount()
    {
        return $this->o_dataAccess->getTotal($this->_getListFilter(true));
    }

    // }}}
    // {{{ getMatchedModelsCount()

    /**
     * Gets the count of matched number of models in the data set
     *
     * @access public
     * @return int The total number of models.
     */
    function getMatchedModelsCount()
    {
        if (is_null($this->o_resultSet)) {
            return 0;
        }
        else {
            return $this->o_resultSet->numRows();
        }
    }

    // }}}
    // {{{ loadNextModel()

    /**
     * Loads the next model from the result set
     *
     * @access public
     * @return bool False if at the end, true otherwise. 
     */
    function loadNextModel()
    {
        if (is_null($this->o_resultSet)) {
            return false;
        }

        $a_data = array();
        if ($this->currentRow >= $this->endRow) {
            return false;
        }

        $result = $this->o_resultSet->fetchInto($a_data, DB_FETCHMODE_DEFAULT, $this->currentRow);
        $this->currentRow++;
        if ($result === DB_OK) {
            $this->o_model->reset();
            $this->o_model->importFromArray($a_data);
            return true;
        }
        else {
            return false; 
        }
    }

    // }}}
    // {{{ performSearch()

    /**
     * Performs the search on the database and prepares the variables for iterating through
     * the result set.
     *
     * @access public 
     * @return True if the search is successful, false otherwise 
     */
    function performSearch()
    {
        $result =& $this->o_dataAccess->getListData(
            $this->_getListFilter(),
            $this->o_list->getSortField(),
            $this->o_list->getSortOrder());

        if (!DB::isError($result)) {
            $this->o_resultSet =& $result;
            return true;
        }
        else {
            return false;
        }
    }

    // }}}
    // {{{ _getListFilter()

    /**
     * Gets the filter for the list.
     *
     * @param bool $in_allRows Get all rows (in which case we don't use the search string)?
     *
     * @access private
     * @return string The SQL filter clause
     */
    function _getListFilter($in_allRows = false)
    {
        $s_searchField = $this->o_list->getSearchField();
        if ($s_searchField == $this->o_list->getAllFieldsKey()) {
            $a_searchFields = array(); 
            foreach ($this->o_list->getSearchableFields(true) as $a_val) {
                $a_searchFields[] = $a_val['search'];
            }
        }
        else {
            $a_searchFields = array($s_searchField);
        }

        $s_searchString = $in_allRows ? '' : $this->o_list->getSearchString();
        $s_filter = $this->o_dataAccess->getListFilter(
                $s_searchString,
                $a_searchFields, 
                $this->filterName,
                $this->filterData);
        return $s_filter;
    }

    // }}}
}
?>
