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

require_once 'DB.php';
require_once dirname(__FILE__) . '/Result.php';

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
     * @type object
     */
    var $o_registry;

    /**
     * The data object.  Used for accessing persistent data 
     * @type object
     */
    var $o_data;

    /**
     * The primary key for the table
     * @type string
     */
    var $primaryKey = 'id';

    /**
     * The table we are working with
     * @type string
     */
    var $table;

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
        $this->connect();
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

        list($pth_dao, $s_class) = FF_DataAccess::getDaoInfo($in_module, $in_app);
        if (!isset($a_instances[$s_class])) {
            if (file_exists($pth_dao)) { 
                require_once $pth_dao;
                $a_instances[$s_class] = new $s_class();
            }
            else {
                FastFrame::fatal("Unable to instantiate DataAccess object for module: $in_module (file: $pth_dao)", __FILE__, __LINE__);
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
     * @return array The path and the class name 
     */
    function getDaoInfo($in_module, $in_app = null)
    {
        $o_registry =& FF_Registry::singleton();
        $s_app = is_null($in_app) ? $o_registry->getCurrentApp() : $in_app;
        $s_driver = strtolower($o_registry->getConfigParam('data/type', 'mysql', array('app' => $s_app)));
        $pth_dao = $o_registry->getAppFile("DataAccess/$s_driver/$in_module.php", $s_app, 'libs');
        $s_class = 'FF_DataAccess_' . $in_module . '_' . $s_driver;
        return array($pth_dao, $s_class);
    }

    // }}}
    // {{{ connect()

    /**
     * Connects to the persistent layer, initializes $this->o_data, and sets the default
     * fetchmode to DB_FETCHMODE_ASSOC
     *
     * @access public
     * @return void
     */
    function connect()
    {
        $result = $this->o_data =& DB::connect($this->o_registry->getDataDsn());
        if (DB::isError($result)) {
            FastFrame::fatal($result, __FILE__, __LINE__); 
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
        // interface
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
        // interface
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
        $o_result =& new FF_Result();
        $s_field = is_null($in_field) ? $this->primaryKey : $in_field;
        $s_query = sprintf('DELETE FROM %s WHERE %s=%s',
                              $this->table,
                              $s_field,
                              $this->o_data->quote($in_value)
                          );
        if (DB::isError($result = $this->o_data->query($s_query))) {
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
        $s_query = sprintf('SELECT COUNT(*) FROM %s WHERE %s=%s',
                              $this->table,
                              $in_dataField,
                              $this->o_data->quote($in_data)
                          );
        if ($in_isUpdate) {
            $s_query .= sprintf(' AND %s!=%s',
                                   $this->primaryKey,
                                   $this->o_data->quote($in_id)
                               );
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
        return date("Y-m-d\tH:i:s", $in_date); 
    }

    // }}}
    // {{{ boolToScalar()

    /**
     * Converts a boolean to the scalar of T and F which are the values stored for boolean
     * fields.
     *
     * @param bool $in_bool The boolean value
     *
     * @access public
     * @return string T if it is true, F otherwise
     */
    function boolToScalar($in_bool)
    {
        return $in_bool ? 'T' : 'F';
    }

    // }}}
    // {{{ getListData()

    /**
     * Queries the database for the information displayed on a list page.
     *
     * @param string $in_where The where condition
     * @param string $in_orderByField The order by field
     * @param string $in_orderByDir The order by direction 
     * @param int $in_offset The offset to start at
     * @param int $in_limit The limit of records
     * @param string $in_fields (optional) The fields to select (default is all fields)
     *
     * @access public
     * @return object The result object
     */
    function &getListData($in_where, $in_orderByField, $in_orderByDir, $in_offset, $in_limit, $in_fields = '*')
    {
        $s_query = sprintf('SELECT %s FROM %s WHERE %s ORDER BY %s %s',
                              $in_fields,
                              $this->table,
                              $in_where,
                              $in_orderByField,
                              $in_orderByDir
                          );
        $s_query = $this->o_data->modifyLimitQuery($s_query, $in_offset, $in_limit);
        return $this->o_data->query($s_query);
    }

    // }}}
    // {{{ getTotal()

    /**
     * Gets the total number of records in the table based on the WHERE condition.
     *
     * @param string $in_where (optional) The where condition.  If not specified, defaults
     *               to 1
     *
     * @access public
     * @return int The number of records in the main table
     */
    function getTotal($in_where = 1)
    {
        $s_query = sprintf('SELECT COUNT(*) FROM %s WHERE %s',
                              $this->table,
                              $in_where
                          );
        $result = $this->o_data->getOne($s_query);
        return $result;
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
     * @return array The array of data or empty array if not found
     */
    function getDataByPrimaryKey($in_id, $in_fields = '*')
    {
        $s_query = sprintf('SELECT %s FROM %s WHERE %s=%s',
                              $in_fields,
                              $this->table,
                              $this->primaryKey,
                              $this->o_data->quote($in_id)
                          );
        if (DB::isError($result = $this->o_data->getAll($s_query))) {
            return array();
        }
        else {
            return @$result[0];
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
}
?>
