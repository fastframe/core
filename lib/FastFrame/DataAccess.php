<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006 The Codejanitor Group                        |
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

require_once 'DB.php';

// }}}
// {{{ class FF_DataAccess 

/**
 * The FF_DataAccess:: class handles the task of making data persistent.  It will save,
 * delete, and edit rows from a table.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package DataAccess
 */

// }}}
class FF_DataAccess {
    // {{{ properties

    /**
     * The registry object
     * @var object
     */
    var $o_registry;

    /**
     * The data object.  Used for accessing persistent data 
     * @var object
     */
    var $o_data;

    /**
     * The primary key for the table
     * @var string
     */
    var $primaryKey = 'id';

    /**
     * The table we are working with
     * @var string
     */
    var $table;

    /**
     * The parameters used to connect
     */
    var $connectParams;

    // }}}
    // {{{ constructor

    /**
     * Initialize any needed class variables
     *
     * @access public
     * @return void
     */
    function FF_DataAccess()
    {
        $this->o_registry =& FF_Registry::singleton(); 
    }

    // }}}
    // {{{ factory()

    /**
     * Attempts to return a concrete DataAccess instance based on $in_app and the type of
     * database driver specified in the config.
     *
     * @param string $in_module The module which is used to determine name of file and class
     *               name.
     * @param string $in_app (optional) The application.  Uses current app if not specified
     *
     * @access public
     * @return object The newly created concrete DataAccess instance
     */
    function &factory($in_module, $in_app = null)
    {
        static $a_instances;
        if (!isset($a_instances)) {
            $a_instances = array();
        }

        list($pth_dao, $s_class, $s_app) = FF_DataAccess::getDaoInfo($in_module, $in_app);
        if (!isset($a_instances[$s_class])) {
            if (file_exists($pth_dao)) { 
                require_once $pth_dao;
                $a_instances[$s_class] = new $s_class();
                $a_instances[$s_class]->connect($s_app);
            }
            else {
                trigger_error("Unable to instantiate DataAccess object for module: $in_module", E_USER_ERROR);
            }
        }

        return $a_instances[$s_class];
    }

    // }}}
    // {{{ getDaoInfo()

    /**
     * Returns the path and class name based on the module, app, and driver for a DataAccess
     * object.
     *
     * @param string $in_module The module which is used to determine name of file and class
     *               name.
     * @param string $in_app (optional) The application.  Uses current app if not specified
     *
     * @access public
     * @return array The path, class name, and app
     */
    function getDaoInfo($in_module, $in_app = null)
    {
        $o_registry =& FF_Registry::singleton();
        $s_app = is_null($in_app) ? $o_registry->getCurrentApp() : $in_app;
        $s_driver = strtolower($o_registry->getConfigParam('data/type', 'mysql', $s_app));
        $pth_dao = $o_registry->getAppFile("DataAccess/$s_driver/$in_module.php", $s_app, 'libs');
        $s_class = 'FF_DataAccess_' . $in_module . '_' . $s_driver;
        return array($pth_dao, $s_class, $s_app);
    }

    // }}}
    // {{{ connect()

    /**
     * Connects to the persistent layer, initializes $this->o_data, and
     * sets the default fetchmode to DB_FETCHMODE_ASSOC
     *
     * @param string $in_app The app in which we are running
     *
     * @access public
     * @return void
     */
    function connect($in_app)
    {
        $this->connectParams = $this->getConnectParams($in_app);
        $this->o_data =& DB::connect($this->connectParams);
        if (DB::isError($this->o_data)) {
            trigger_error($this->o_data->getMessage(), E_USER_ERROR);
        } 

        $this->o_data->setFetchMode(DB_FETCHMODE_ASSOC);
    }

    // }}}
    // {{{ update()

    /**
     * Updates a row that already exists in the table 
     *
     * @param array $in_data The new data to update 
     *
     * @access public
     * @return object The Result object 
     */
    function update($in_data)
    {
        $s_where = $this->primaryKey . '=' . $this->o_data->quoteSmart($in_data[$this->primaryKey]);
        $o_result = new FF_Result();
        if (PEAR::isError($result = $this->o_data->autoExecute($this->table, $in_data, DB_AUTOQUERY_UPDATE, $s_where))) {
            $o_result->addMessage($result->getMessage());
            $o_result->setSuccess(false);
        }

        return $o_result;
    }

    // }}}
    // {{{ add()

    /**
     * Adds a new row to the table 
     *
     * @param array $in_data The array of data to add 
     *
     * @access public
     * @return object The result object 
     */
    function add($in_data)
    {
        $o_result = new FF_Result();
        if (PEAR::isError($result = $this->o_data->autoExecute($this->table, $in_data))) {
            $o_result->addMessage($result->getMessage());
            $o_result->setSuccess(false);
        }

        return $o_result;
    }

    // }}}
    // {{{ remove()

    /**
     * Removes the model from the persistent layer 
     *
     * @param string $in_value The value of the field
     * @param string $in_field (optional) The field to match upon.  If not passed we use the
     *               primaryId variable.
     *
     * @access public
     * @return object Result object 
     */
    function remove($in_value, $in_field = null)
    {
        $o_result = new FF_Result();
        $s_field = is_null($in_field) ? $this->primaryKey : $in_field;
        $s_stmt = $this->o_data->prepare("DELETE FROM $this->table WHERE $s_field = ?");
        if (DB::isError($result = $this->o_data->execute($s_stmt, $in_value))) {
            $o_result->addMessage($result->getMessage());
            $o_result->setSuccess(false);
        }

        return $o_result;
    }

    // }}}
    // {{{ isDataUnique()

    /**
     * Checks to see if data is unique for the column of data it goes into.
     *
     * @param string $in_dataField The field name in the database to check against
     * @param string $in_data The data to check for uniqueness
     * @param int $in_id The id value so we can check against the primary key
     * @param bool $in_isUpdate Is this an update?  If so we will allow for the value to be
     *             in its existing row.
     * @param string $in_where (optional) Any additional statements to add to the WHERE
     *               clause
     *
     * @access public
     * @return bool True if it is unique, false otherwise
     */
    function isDataUnique($in_dataField, $in_data, $in_id, $in_isUpdate, $in_where = null)
    {
        $s_query = "SELECT COUNT(*) FROM $this->table WHERE $in_dataField = " . $this->o_data->quoteSmart($in_data);
        if ($in_isUpdate) {
            $s_query .= " AND $this->primaryKey != " . $this->o_data->quoteSmart($in_id);
        }

        if (!is_null($in_where)) {
            $s_query .= ' ' . $in_where;
        }

        if ($this->o_data->getOne($s_query) == 0) {
            return true;
        }
        else {
            return false;
        }
    }

    // }}}
    // {{{ timestampToISODate()

    /**
     * Converts a unix timestamp into an ISO date for use in SQL queries.
     *
     * @param int $in_date The date to convert
     *
     * @access public
     * @return string The ISO date
     */
    function timestampToISODate($in_date)
    {
        if ($in_date == 0) {
            return "0000-00-00\t00:00:00";
        }
        // Allows a date to be set way in the future (PHP chokes at 2032)
        elseif ($in_date == -1) {
            return "2050-01-01\t00:00:00";
        }
        else {
            return date("Y-m-d\tH:i:s", $in_date);
        }
    }

    // }}}
    // {{{ getListData()

    /**
     * Queries the database for the information displayed on a list page.
     *
     * @param string $in_where The where condition
     * @param string $in_orderByField The order by field
     * @param bool $in_orderByDir The order by direction (true => ASC, false => DESC)
     * @param string $in_fields (optional) The fields to select (default is all fields)
     *
     * @access public
     * @return object The result object
     */
    function getListData($in_where, $in_orderByField, $in_orderByDir, $in_fields = '*')
    {
        $s_query = "SELECT $in_fields FROM $this->table WHERE $in_where ORDER BY $in_orderByField " .
                    $this->_getOrderByDirection($in_orderByDir);
        return $this->o_data->query($s_query);
    }

    // }}}
    // {{{ getListFilter()

    /**
     * Creates a filter that can be added as a SQL WHERE clause.  This uses the search
     * fields and strings and is suitable for grabbing the fields that should be present in
     * this list.
     *
     * @param string $in_searchString The string to search for
     * @param array $in_searchFields The array of fields to search.
     * @param string $in_filter The name of an additional filter to apply in case the list
     *               needs to be further limited. 
     * @param array $in_filterData Any associated data or flags that go with the filter.
     *              tableName can be passed in if the field need to have a table name given
     *              to them.
     *
     * @access public 
     * @return string A WHERE condition for the list data
     */
    function getListFilter($in_searchString, $in_searchFields, $in_filter, $in_filterData)
    {
        // handle dates (formats of mm/dd/yyyy and mm-dd-yyyy).
        // to search between two days: date1 - date2
        if (preg_match(':^(\d{1,2})[-/](\d{1,2})[-/](\d{4})( ?- ?(\d{1,2})[-/](\d{1,2})[-/](\d{4}))?:', 
                        $in_searchString, 
                        $a_parts)) {
            $s_startDate = $a_parts[3] . sprintf('%02d', $a_parts[1]) . sprintf('%02d', $a_parts[2]) . '000000';
            // they specified an end date
            if (isset($a_parts[4])) {
                $s_endDate = $a_parts[7] . sprintf('%02d', $a_parts[5]) . sprintf('%02d', $a_parts[6]) . '235959';
            }
            // if they did not specify an end date, then it is the end of the day
            else {
                $s_endDate = substr($s_startDate, 0, 8) . '235959';
            }

            $s_searchCondition = '`%field%` BETWEEN ' . $s_startDate . ' AND ' . $s_endDate; 
        }
        else {
            $s_searchCondition = '`%field%` LIKE ' . $this->o_data->quoteSmart('%' . $in_searchString . '%');
        }

        if (isset($in_filterData['tableName'])) {
            $in_filterData['tableName'] .= '.';
        }
        else {
            $in_filterData['tableName'] = '';
        }

        if (count($in_searchFields) != 0 && !empty($in_searchString)) {
            $tmp_fields = array();
            foreach ($in_searchFields as $s_field) {
                $tmp_fields[] = str_replace('`%field%`', 
                        $in_filterData['tableName'] .  $this->o_data->quoteIdentifier($s_field) , $s_searchCondition);
            }

            $s_where = implode(" OR \n", $tmp_fields);
        }
        else {
            // Allow for an empty search field list 
            $s_where = '1=1';
        }

        return $s_where;
    }

    // }}}
    // {{{ getDataByPrimaryKey()

    /**
     * Gets one row of data based on the primary key of the table
     *
     * @param int $in_id The primary key value
     * @param string $in_fields (optional) The fields to select (default is all fields)
     *
     * @access public
     * @return array The array of dat
     */
    function getDataByPrimaryKey($in_id, $in_fields = '*')
    {
        // Can't query against empty id
        if (FastFrame::isEmpty($in_id)) {
            return array();
        }

        $s_query = "SELECT $in_fields FROM $this->table WHERE $this->primaryKey = ?";
        if (DB::isError($result = $this->o_data->getAll($s_query, array($in_id)))) {
            return array();
        }
        else {
            return @$result[0];
        }
    }

    // }}}
    // {{{ getFieldByPrimaryKey()

    /**
     * Gets a field by the primary key.
     *
     * @param int $in_id The primary key value
     * @param string $in_field The field to get
     *
     * @access public
     * @return string The field value
     */
    function getFieldByPrimaryKey($in_id, $in_field)
    {
        // Can't query against empty id
        if (FastFrame::isEmpty($in_id)) {
            return null;
        }

        $s_query = "SELECT $in_field FROM $this->table WHERE $this->primaryKey = ?";
        if (DB::isError($result = $this->o_data->getOne($s_query, $in_id))) {
            return null; 
        }
        else {
            return $result;
        }
    }

    // }}}
    // {{{ getPrimaryKey()

    /**
     * Returns the primary key field 
     *
     * @access public
     * @return string The primary key field 
     */
    function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    // }}}
    // {{{ getNextId()

    /**
     * Gets the next id available in the table
     *
     * @access public
     * @return int The next id
     */
    function getNextId()
    {
        return $this->o_data->nextId($this->table);
    }

    // }}}
    // {{{ getConnectParams()

    /**
     * Returns an array of the necessary parameters for connecting to
     * the database.
     *
     * @param string $in_app The app in which we are running
     *
     * @access public
     * @return array An array of DB connection options
     */
    function getConnectParams($in_app)
    {
        return array(
                'phptype' => $this->o_registry->getConfigParam('data/type', null, $in_app),
                'username' => $this->o_registry->getConfigParam('data/username', null, $in_app),
                'password' => $this->o_registry->getConfigParam('data/password', null, $in_app),
                'hostspec' => $this->o_registry->getConfigParam('data/host', null, $in_app),
                'database' => $this->o_registry->getConfigParam('data/database', null, $in_app));
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
        if ($in_order === 'ASC' || $in_order === 'DESC') {
            return $in_order;
        }
        else {
            return $in_order ? 'ASC' : 'DESC';
        }
    }

    // }}}
}
?>
