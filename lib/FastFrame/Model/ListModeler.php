<?php
/** $Id: ListModeler.php,v 1.1 2003/03/13 18:35:23 jrust Exp $ */
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
    
    // }}}
    // {{{ constructor

    /**
     * Initialize any needed class variables
     *
     * @param object $in_listObject The list object
     * @param object $in_modelObject The model object
     *
     * @access public
     * @return void
     */
    function FF_Model_ListModeler(&$in_listObject, &$in_modelObject)
    {
        $this->o_list =& $in_listObject;
        $this->o_model =& $in_modelObject;
        $this->o_dataAccess =& $this->o_model->getDataAccessObject();
        $this->_performSearch();
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
        $s_query = sprintf('SELECT COUNT(*) FROM %s',
                              $this->o_dataAccess->table
                          );
        return $this->o_dataAccess->o_data->getOne($s_query);
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
        $s_query = sprintf('SELECT COUNT(*) FROM %s WHERE %s',
                              $this->o_dataAccess->table,
                              $this->_getWhereCondition()
                          );
        return $this->o_dataAccess->o_data->getOne($s_query);
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
    // {{{ _getWhereCondition()

    /**
     * Creates a condition that can be added to a SQL WHERE clause.  This is uses the search
     * fields and strings and is suitable for grabbing the fields that should be present in
     * this list.
     *
     * @access private 
     * @return string A WHERE condition 
     */
    function _getWhereCondition()
    {
        $s_searchField = $this->o_list->getSearchField();
        $s_searchCondition = '%field% LIKE ' . $this->o_dataAccess->o_data->quote('%' . $this->o_list->getSearchString() . '%');
        if (!empty($s_searchField)) {
            $tmp_fields = array();
            if ($s_searchField == $this->o_list->getAllFieldsKey()) {
                foreach ($this->o_list->getSearchableFields(true) as $a_val) {
                    $tmp_fields[] = str_replace('%field%', $a_val['search'], $s_searchCondition);
                }
            }
            else {
                $tmp_fields[] = str_replace('%field%', $s_searchField, $s_searchCondition);
            }

            $s_where = implode(" OR \n", $tmp_fields);
        }
        else {
            $s_where = '1=1';
        }

        return $s_where;
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
    // {{{ _performSearch()

    /**
     * Performs the search on the database and prepares the variables for iterating through
     * the result set.
     *
     * @access private 
     * @return void 
     */
    function _performSearch()
    {
        $s_query = sprintf('SELECT * FROM %s WHERE %s ORDER BY %s %s',
                              $this->o_dataAccess->table,
                              $this->_getWhereCondition(),
                              $this->o_list->getSortField(), 
                              $this->_getOrderByDirection($this->o_list->getSortOrder())
                          );
        $s_query = $this->o_dataAccess->o_data->modifyLimitQuery($s_query, $this->o_list->getRecordOffset(), $this->o_list->getDisplayLimit()); 
        if (!DB::isError($result =& $this->o_dataAccess->o_data->query($s_query))) {
            $this->o_resultSet =& $result;
        }
    }

    // }}}
}
?>
