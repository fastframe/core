<?php
/** $Id: SQL.php,v 1.1 2003/01/03 22:42:45 jrust Exp $ */
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
    // {{{ string  updateInsert()

    /**
     * Does an update or insert query, depending on whether the condition is met
     *
     * @param object    $in_db The database object
     * @param string    $in_table The table 
     * @param array     $in_fields The fields to update 
     * @param array     $in_data The data to fill into the fields 
     * @param string    $in_condition (optional) The where condition to meet (for UPDATE query or
     *                                choosing to do INSERT)
     *
     * @access public
     * @return string The UPDATE/INSERT statement
     */
    function updateInsert(&$in_db, $in_table, $in_fields, $in_data, $in_condition = null) 
    {
        if (!is_null($in_condition) && $in_db->getOne('SELECT 1 FROM ' . $in_table . ' WHERE ' . $in_condition)) {
            $s_query = 'UPDATE ' . $in_table . ' SET ' . implode(' = %s, ', $in_fields) . ' = %s WHERE ' . $in_condition;
        }
        else {
            $s_query = 'INSERT INTO ' . $in_table . ' (' . implode(', ', $in_fields) . ') VALUES (' . implode(', ', array_fill(0, sizeof($in_fields), '%s')) . ')';
        }
    
        $query = vsprintf($query, array_map(array($in_db, 'quote'), $in_data));
        return $in_db->query($query);
    }

    // }}}
    // {{{ array   prepareSearch()

    /**
     * Prepare the search fields, query and sort column table prefix
     *
     * This function is merely a line saver, since all the list pages must
     * run this query to ready the search clause of the query.  Given the
     * data for the fields in the current module, this function returns an
     * array of sortable fields to be used in generate a WHERE clause and to
     * be used in the selet element on the output page for selecting a sort
     * field.  It returns the search clause.  In the process, it is convenient
     * to determine the table alias prefix for the sort field.
     *
     * @param array information for the current module
     *
     * @return array the search fields, the search clause and the sort field table alias prefix
     */
    function prepareSearch(&$in_db, $in_dataFields, $in_persistentData)
    {
        global $tpl_prefix;
        
        $searchFields = array();
    
        // find the prefix for the table of the sort column
        foreach ($in_dataFields as $dataField) {
            $tmp_tableAbbr = substr($dataField['table'], strlen($tpl_prefix), 1) . '.';
    
            if ($dataField['field'] == $in_persistentData['sortField']) {
                if (is_null($dataField['table'])) {
                    $sortTableAbbr = ''; 
                }
                else {
                    $sortTableAbbr = $tmp_tableAbbr;
                }
            }
            
            if ($dataField['search']) {
                $searchFields[$tmp_tableAbbr . $dataField['field']] = $dataField['title'];
            }
        }
    
        // make sure that our search field is an allowed one
        if ($in_persistentData['searchField'] != 'all' && !isset($searchFields[$in_persistentData['searchField']])) {
            $in_persistentData['searchField'] = '';
            $in_persistentData['searchString'] = '';
        }
    
        $searchClause = FastFrame_SQL::sqlQuery(&$in_db, $in_persistentData['searchField'] == 'all' ? array_keys($searchFields) : $in_persistentData['searchField'], $in_persistentData['searchString']);
    
        return array($sortTableAbbr, $searchFields, $searchClause);
    }
    
    // }}}
    // {{{ string  sqlLimit()

    function sqlLimit($in_pageOffset, $in_limit)
    {
        if ($in_limit > 0) {
            return 'LIMIT ' . ($in_pageOffset - 1) * $in_limit . ', ' . $in_limit;
        }
        else {
            return '';
        }
    }

    // }}}
    // {{{ string  sqlQuery()

    function sqlQuery(&$in_db, $in_fields, $in_query)
    {
        if ($in_query == '' || $in_fields == '' || (is_array($in_fields) && empty($in_fields))) {
            return '1 = 1';
        }

        $query = str_replace('*', '%', $in_query);
        settype($in_fields, 'array');
        
        foreach($in_fields as $field) {
            $sql[] = $field . ' LIKE ' . $in_db->quote($query);
        }
        
        $sql = implode(' OR ', $sql);
        return $sql;
    }

    // }}}
}
?>
