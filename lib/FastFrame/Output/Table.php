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
// {{{ class FF_Output_Table

/**
 * The FF_Output_Table:: class  provides methods for creating different
 * types of tables.
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
     * The table type
     * @var string
     */
    var $type;

    /**
     * The output object
     * @var object
     */
    var $o_output;

    /**
     * The widget object
     * @var object
     */
    var $o_widget;

    /**
     * The total number of columns of table
     * @var int
     */
    var $numColumns = 2;

    /**
     * Table header text
     * @var string
     */
    var $tableHeaderText;

    /**
     * Alternating row colors?
     * @var bool
     */
    var $alternateRowColors = false;

    /**
     * The table headers.
     * @var array
     */
    var $tableHeaders = array(
        0 => array(
            'title' => 'Example Element',
            'data' => 'example data', 
            'titleStyle' => 'style="text-align: right;"',
            'dataStyle' => '',
        )
    );

    // }}}
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @param string $in_type The table type (options are: multiColumn,
     *        twoColumn)
     *
     * @access public
     * @return void
     */
    function FF_Output_Table($in_type)
    {
        $this->type = $in_type;
        $this->o_output =& FF_Output::factory();
    }

    // }}}
    // {{{ render()

    /**
     * Renders the table, based on the type
     *
     * @access public
     * @return void
     */
    function render()
    {
        switch($this->type) {
            case 'multiColumn':
                $this->o_widget =& new FF_Smarty('multiColumnTable');
                $this->_renderMultiColumnTable();
                break;
            case 'twoColumn':
            default:
                $this->o_widget =& new FF_Smarty('twoColumnTable');
                $this->_renderTwoColumnTable();
        }

        $this->setTableVars();
    }

    // }}}
    // {{{ setTableVars()

    /**
     * Sets table-wide variables
     *
     * @access public
     * @return void
     */
    function setTableVars()
    {
        $this->o_widget->assign(array('S_table_columns' => $this->numColumns,
                'T_table_header' => $this->tableHeaderText, 'has_table_header' => true));
    }

    // }}}
    // {{{ _renderTwoColumnTable()

    /**
     * Processes the table headers by registering the appropriate html.
     * Creates a basic two column table of | title | data |
     *
     * @access public
     * @return void 
     */
    function _renderTwoColumnTable()
    {
        $i = 0;
        foreach ($this->tableHeaders as $tmp_header) {
            $tmp_class = $this->alternateRowColors ? $this->o_output->toggleRow($i++) : 'primaryRow';
            $this->o_widget->append('rows', array(
                        'S_table_row' => 'class="' . $tmp_class . '"',
                        'has_field_cell' => isset($tmp_header['title']), true, 'has_content_cell' => true,
                        'T_table_field_cell' => isset($tmp_header['title']) ? $tmp_header['title'] : '',
                        'S_table_field_cell' => isset($tmp_header['titleStyle']) ? $tmp_header['titleStyle'] : '',
                        'T_table_content_cell' => $this->o_output->processCellData($tmp_header['data'],
                            (isset($tmp_header['dataIsSafe']) ? $tmp_header['dataIsSafe'] : false)), 
                        'S_table_content_cell' => isset($tmp_header['dataStyle']) ? $tmp_header['dataStyle'] : ''));
        }
    }

    // }}}
    // {{{ _renderMultiColumnTable()

    /**
     * Processes the table headers by registering the appropriate html.  Creates a
     * multi-column multi-row table of
     * title    |   title
     * data     |   data
     * data     |   data
     * It assumes that the first element of the tableHeaders is an array of the title cells
     * and title attributes and that the rest of the elments are arrays of data cells and data
     * attributes.  An example data structure:
     * $a_data = array(
     *           0 => array(0 => array('title' => 'foo')),
     *           1 => array(0 => array('data' => 'test')),
     *           2 => array('data' => 'test2'))
     *
     * @access private
     * @return void 
     */
    function _renderMultiColumnTable()
    {
        $this->setNumColumns(count($this->tableHeaders[0]));
        if (isset($this->tableHeaders[0][0]['title'])) {
            $tmp_headers = array_shift($this->tableHeaders);
            $this->o_widget->assign('has_field_row', true);
            foreach ($tmp_headers as $tmp_header) {
                $this->o_widget->append('fieldCells', array(
                            'T_table_field_cell' => $tmp_header['title'],
                            'S_table_field_cell' => isset($tmp_header['titleStyle']) ? $tmp_header['titleStyle'] : ''));
            }
        }

        $i = 0;
        foreach ($this->tableHeaders as $tmp_headers) {
            $tmp_class = $this->alternateRowColors ? $this->o_output->toggleRow($i++) : 'primaryRow';
            $a_cells = array();
            foreach ($tmp_headers as $tmp_header) {
                $a_cells[] = array(
                        'T_table_content_cell' => $this->o_output->processCellData($tmp_header['data'],
                            (isset($tmp_header['dataIsSafe']) ? $tmp_header['dataIsSafe'] : false)),
                        'S_table_content_cell' => isset($tmp_header['dataStyle']) ? $tmp_header['dataStyle'] : '');
            }

            $this->o_widget->append('rows', array(
                        'S_table_row' => 'class="' . $tmp_class . '"', 'cells' => $a_cells));
        }
    }

    // }}}
    // {{{ setAlternateRowColors()

    /**
     * Sets whether or not this table should try and use alternating row
     * colors.
     *
     * @param bool $in_alternate Alternate row colors?
     *
     * @access public
     * @return void
     */
    function setAlternateRowColors($in_alternate)
    {
        $this->alternateRowColors = $in_alternate;
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
    // {{{ getWidgetObject()

    /**
     * Gets the table widget object
     *
     * @access public
     * @return object The widget object
     */
    function &getWidgetObject()
    {
        return $this->o_widget;
    }

    // }}}
}
?>
