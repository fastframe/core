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
// {{{ class FF_Model_ListModeler

/**
 * The FF_Model_ListModeler:: is a class to help in producing a list of models.
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
class FF_Model_ListModeler {
    // {{{ properties

    /**
     * The List object 
     * @type object 
     */
    var $o_list;

    /**
     * The model object
     * @type object
     */
    var $o_model;

    /**
     * The data access object 
     * @type object 
     */
    var $o_dataAccess;

    /**
     * The result set object
     * @type object
     */
    var $o_resultSet = null;

    /**
     * The filter to apply to the data
     * @type string
     */
    var $filterName = null;

    // }}}
    // {{{ constructor

    /**
     * Initialize any needed class variables
     *
     * @param object $in_listObject The list object
     * @param object $in_modelObject The model object
     * @param string $in_filter The name of the filter to apply to the list
     *
     * @access public
     * @return void
     */
    function FF_Model_ListModeler(&$in_listObject, &$in_modelObject, $in_filter)
    {
        $this->o_list =& $in_listObject;
        $this->o_model =& $in_modelObject;
        $this->o_dataAccess =& $this->o_model->getDataAccessObject();
        $this->filterName = $in_filter;
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
        return $this->o_dataAccess->getTotal();
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
        return $this->o_dataAccess->getTotal($this->_getListFilter());
    }

    // }}}
    // {{{ getDisplayedModelsCount()

    /**
     * Gets the number of models that are going to be displayed on the page with the
     * current result set.
     *
     * @access public
     * @return int The number of displayed models
     */
    function getDisplayedModelsCount()
    {
        if (is_null($this->o_resultSet)) {
            return 0;
        }
        else {
            return $this->o_resultSet->numRows();
        }
    }

    // }}}
    // {{{ getNextModel()

    /**
     * Gets the next model from the result set
     *
     * @access public
     * @return mixed A model with the new data from the next set or false if at the end 
     */
    function &getNextModel()
    {
        if (is_null($this->o_resultSet)) {
            return false;
        }

        $a_data = array();
        $result = $this->o_resultSet->fetchInto($a_data);
        if ($result === DB_OK) {
            $this->o_model->reset();
            $this->o_model->importFromArray($a_data);
            return $this->o_model;
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
            $this->_getOrderByDirection($this->o_list->getSortOrder()),
            $this->o_list->getRecordOffset(),
            $this->o_list->getDisplayLimit() 
        );

        if (!DB::isError($result)) {
            $this->o_resultSet =& $result;
            return true;
        }
        else {
            return false;
        }
    }

    // }}}
    // {{{ _getOrderByDirection()

    /**
     * Returns an order string by handling either true (ASC)/false (DESC) type notation or
     * regular ASC/DESC strings.
     *
     * @param mixed $in_order The order
     *
     * @access private 
     * @return string Either ASC or DESC
     */
    function _getOrderByDirection($in_order)
    {
        $in_order = strtoupper($in_order);
        if ($in_order == 'ASC' || $in_order == 'DESC') {
            return $in_order;
        }
        else {
            return (int) $in_order ? 'ASC' : 'DESC';
        }
    }

    // }}}
    // {{{ _getListFilter()

    /**
     * Gets the filter for the list.
     *
     * @access private
     * @return string The SQL filter clause
     */
    function _getListFilter()
    {
        static $s_filter;
        if (!isset($s_filter)) {
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

            $s_filter = $this->o_dataAccess->getListFilter(
                    $this->o_list->getSearchString(), 
                    $a_searchFields, 
                    $this->filterName);
        }

        return $s_filter;
    }
}
?>
