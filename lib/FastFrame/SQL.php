<?php
/** $Id: SQL.php,v 1.4 2003/01/21 01:22:35 jrust Exp $ */
// {{{ class FastFrame

/**
 * The SQL class provides methods for creating common SQL statements. 
 * This is a completely static class, all methods can be called directly.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lesser.html.
 *
 * @version Revision: 1.0 
 * @author  Dan Allen <dan@mojavelinux.com>
 * @author  Jason Rust <jrust@bsiweb.com>
 * @access  public
 * @package FastFrame
 */

// }}}
class FastFrame_SQL {
    // {{{ string  backTickString()

    /**
     * Enclose the database/table/column name in backticks if it is not already enclosed in
     * them
     *
     * @param $in_string The database/table/column name
     *
     * @access public
     * @return string The backticked string
     */
    function backTickString($in_string)
    {
        if (strpos($in_string, '`') !== 0) {
            return '`' . $in_string . '`';
        }
        else {
            return $in_string;
        }
    }

    // }}}
    // {{{ string  getOrderString()

    /**
     * Returns an order string by handling either true (ASC)/false (DESC) type notation or
     * regular ASC/DESC strings.
     *
     * @param mixed $in_order The order
     *
     * @access public
     * @return string Either ASC or DESC
     */
    function getOrderString($in_order)
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
}
?>
