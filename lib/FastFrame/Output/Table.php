<?php
/** $Id: Table.php,v 1.4 2003/03/19 00:36:01 jrust Exp $ */
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

require_once dirname(__FILE__) . '/../Output.php';

// }}}
// {{{ class FF_Output_Table

/**
 * The FF_Output_Table:: class provides methods to create a table using
 * a combination of table headers and templates.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package Output 
 */

// }}}
class FF_Output_Table {
    // {{{ properties

    /**
     * The output object
     * @type object
     */
    var $o_output;

    /**
     * The namespace for the table
     * @type string
     */
    var $tableNamespace;

    /**
     * The table name
     * @type string
     */
    var $tableName = 'generic_table';

    /**
     * The total number of columns of table
     * @type int
     */
    var $numColumns = 2;

    /**
     * Table header text
     * @type string
     */
    var $tableHeaderText = 'Information';

    /**
     * The table headers.
     * @type array
     */
    var $tableHeaders = array(
        0 => array(
            'title' => 'Example Element',
            'data' => 'exmaple data', 
            'titleStyle' => 'style="text-align: right;"',
            'dataStyle' => '',
        )
    );

    // }}}
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function FF_Output_Table()
    {
        $this->o_output =& FF_Output::singleton();
    }

    // }}}
    // {{{ beginTable()

    /**
     * Begins a table
     *
     * @access public
     * @return void
     */
    function beginTable()
    {
        $this->tableNamespace = $this->o_output->branchBlockNS('CONTENT_MIDDLE', $this->tableName, 'genericTable.tpl', 'file');
        $this->o_output->assignBlockData(
            array(
                'S_TABLE_COLUMNS' => $this->numColumns,
                'T_table_header' => $this->tableHeaderText,
                'S_table_header' => 'style="text-align: center;"',
            ),
            $this->tableName 
        );
        $this->o_output->touchBlock($this->tableNamespace . 'switch_table_header');
    }

    // }}}
    // {{{ renderTwoColumnTable()

    /**
     * Processes the table headers by registering the appropriate html.  Creates a basic
     * two column table of | title | data |
     *
     * @access public
     * @return void 
     */
    function renderTwoColumnTable()
    {
        $this->beginTable();
        foreach ($this->tableHeaders as $tmp_header) {
            $this->o_output->touchBlock($this->tableNamespace . 'table_row');
            $this->o_output->cycleBlock($this->tableNamespace . 'table_field_cell');
            $this->o_output->cycleBlock($this->tableNamespace . 'table_content_cell');
            $tmp_style = isset($tmp_header['titleStyle']) ? $tmp_header['titleStyle'] : '';
            $this->o_output->assignBlockData(
                array(
                    'T_table_field_cell' => $tmp_header['title'],
                    'S_table_field_cell' => $tmp_style,
                ),
                $this->tableNamespace . 'table_field_cell'
            );
            $tmp_style = isset($tmp_header['dataStyle']) ? $tmp_header['dataStyle'] : '';
            $this->o_output->assignBlockData(
                array(
                    'T_table_content_cell' => $tmp_header['data'], 
                    'S_table_content_cell' => $tmp_style,
                ),
                $this->tableNamespace . 'table_content_cell'
            );
        }
    }

    // }}}
    // {{{ setTableHeaders()

    /**
     * Sets the headers that we make the table out of.  Has the form of 'data' => the
     * cell data, 'title' => the cell description, 'titleStyles'/'dataStyles' => any
     * attributes to apply to the title and data cells respectively.
     *
     * @param array $in_headers The table headers
     *
     * @access public
     * @return array
     */
    function setTableHeaders($in_headers)
    {
        $this->tableHeaders = $in_headers;
    }

    // }}}
    // {{{ setTableHeaderText()
    
    /**
     * Sets the description of the table 
     *
     * @param string $in_text The table header text
     *
     * @access public
     * @return void 
     */
    function setTableHeaderText($in_text)
    {
        $this->tableHeaderText = $in_text; 
    }

    // }}}
    // {{{ getTableName()

    /**
     * Gets the table name
     *
     * @access public
     * @return string The name given to the table 
     */
    function getTableName()
    {
        return $this->tableName;
    }

    // }}}
    // {{{ setTableName()

    /**
     * Sets the table name
     *
     * @param string $in_tableName The table name
     *
     * @access public
     * @return void
     */
    function setTableName($in_tableName)
    {
        $this->tableName = $in_tableName;
    }

    // }}}
    // {{{ getTableNamespace()

    /**
     * Gets the table namespace
     *
     * @access public
     * @return string The table namespace 
     */
    function getTableNamespace()
    {
        return $this->tableNamespace;
    }

    // }}}
    // {{{ getNumColumns()

    /**
     * Gets the number of columns the table has 
     *
     * @access public
     * @return int The number of table columns 
     */
    function getNumColumns()
    {
        return $this->numColumns;
    }

    // }}}
    // {{{ setNumColumns()

    /**
     * Sets the number of columns the table has 
     *
     * @param $in_numCols The number of columns
     * @access public
     * @return void 
     */
    function setNumColumns($in_numCols)
    {
        $this->numColumns = $in_numCols;
    }

    // }}}
}
?>
