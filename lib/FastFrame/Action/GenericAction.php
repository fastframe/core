<?php
/** $Id: GenericAction.php,v 1.1 2003/02/06 18:48:10 jrust Exp $ */
// {{{ requires

require_once dirname(__FILE__) . '/../ActionHandler.php';
require_once dirname(__FILE__) . '/../Application.php';
require_once dirname(__FILE__) . '/../Output.php';

// }}}
// {{{ class ActionHandler_GenericAction

/**
 * The ActionHandler_GenericAction:: class has methods and properties that are generally
 * useful to all actions. 
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lesser.html.
 *
 * @author  Jason Rust <jrust@rustyparts.com>
 * @version Revision: 1.0 
 * @access  public
 * @package ActionHandler 
 */

// }}}
class ActionHandler_GenericAction {
    // {{{ properties

    /**
     * FastFrame_Registry instance
     * @type object
     */
    var $o_registry;

    /**
     * Output instance
     * @type object 
     */
    var $o_output;

    /**
     * ActionHandler instance
     * @type object
     */
    var $o_action;

    /**
     * Application instance
     * @type object
     */
    var $o_application;

    /**
     * The namespace for the generic table
     * @type string
     */
    var $tableNamespace;

    /**
     * The total number of columns of the generic table
     * @type int
     */
    var $numColumns = 2;

    // }}}
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function ActionHandler_GenericAction()
    {
        $this->o_registry =& FastFrame_Registry::singleton();
        $this->o_output =& FastFrame_Output::singleton();
        $this->o_action =& ActionHandler::singleton();
    }

    // }}}
    // {{{ run()

    /**
     * Interface run class
     *
     * @access public
     * @returns void
     */
    function run()
    {
        return PEAR::raiseError(null, FASTFRAME_ERROR, null, E_USER_ERROR, "Run not implemented for this action!", 'FastFrame_Error', true);
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
        $this->tableNamespace = $this->o_output->branchBlockNS('CONTENT_MIDDLE', 'generic_table', 'genericTable.tpl', 'file');
        $this->o_output->assignBlockData(
            array(
                'S_TABLE_COLUMNS' => $this->numColumns,
                'T_table_header' => $this->getTableHeaderText(),
                'S_table_header' => 'style="text-align: center;"',
            ),
            'generic_table' 
        );
        $this->o_output->touchBlock($this->tableNamespace . 'switch_table_header');
    }

    // }}}
    // {{{ processTableHeaders()

    /**
     * Processes the table headers by registering the appropriate html.  Creates a basic
     * table of | title | data |
     *
     * @access public
     * @return private
     */
    function processTableHeaders()
    {
        foreach ($this->getTableHeaders() as $tmp_header) {
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
    // {{{ getTableHeaders()

    /**
     * Gets the headers that we make a basic table out of.  Has the form of 'data' => the
     * cell data, 'title' => the cell description, 'titleStyles'/'dataStyles' => any
     * attributes to apply to the title and data cells respectively.
     *
     * @access public
     * @return array
     */
    function getTableHeaders()
    {
        $a_headers = array();
        $a_headers[] = array(
            'title' => _('Example Element'),
            'data' => 'exmaple data', 
            'titleStyle' => 'style="text-align: right;"',
            'dataStyle' => '',
        );
        return $a_headers;
    }

    // }}}
    // {{{ getTableHeaderText()
    
    /**
     * Gets the description of the table 
     *
     * @access public
     * @return string The text for the header of the table.
     */
    function getTableHeaderText()
    {
        return _('Information');
    }

    // }}}
    // {{{ output()

    /**
     * Outputs the template data to the browser.  Should be the last call in a run() method
     * when it is ready to display the html.
     *
     * @access public
     * @return void
     */
    function output()
    {
        $this->o_output->renderPageType();
        $this->o_output->prender();
    }

    // }}}
}
?>
