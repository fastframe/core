<?php
/** $Id: List.php,v 1.14 2003/04/10 21:51:40 jrust Exp $ */
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

require_once dirname(__FILE__) . '/Output.php';
require_once 'HTML/QuickForm.php';

// }}}
// {{{ class FF_List

/**
 * The FF_List:: class provides the necessary methods for handling lists of items and
 * putting those lists into an HTML page.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package FastFrame
 */

// }}}
class FF_List {
    // {{{ properties

    /**
     * Output instance
     * @type object
     */
    var $o_output;

    /**
     * The column data. 
     * @type array
     */
    var $columnData = array();

    /**
     * The searchable fields. 
     * @type array
     */
    var $searchableFields = array();

    /**
     * The key for searching all fields
     * @type string
     */
    var $allFieldsKey = '**all**'; 

    /**
     * The total number of records in the data set 
     * @type int
     */
    var $totalRecords = 0;

    /**
     * The total number of matched records in the data set 
     * @type int
     */
    var $matchedRecords = 0;

    /**
     * The total number of records displayed on the page
     * @type int
     */
    var $displayedRecords = 0;

    /**
     * The current sort field 
     * @type string
     */
    var $sortField;

    /**
     * The current sort order (1 = ASC, 0 = DESC)
     * @type int
     */
    var $sortOrder;

    /**
     * The current search string 
     * @type int
     */
    var $searchString;

    /**
     * The current field being searched 
     * @type int
     */
    var $searchField;

    /**
     * Whether or not we are in the advanced list page 
     * @type bool
     */
    var $advancedList;

    /**
     * The current page offset 
     * @type int
     */
    var $pageOffset;

    /**
     * The current limit of items per page
     * @type int
     */
    var $displayLimit;

    /**
     * Any persistent data which should be passed as hidden fields (i.e. actionId) 
     * @type array
     */
    var $persistentData = array();

    // }}}
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @param string $in_defaultSortField The default field to sort on
     * @param int $in_defaultSortOrder The default sort order (1 = ASC, 0 = DESC)
     * @param int $in_defaultDisplayLimit The default display limit
     * @param array $in_persistentData (optional) Persistent data in the form of 'name' =>
     *              'value' that should be passed on, such as the actionId
     * @param array $in_columnData (optional) A multidimensional array with the primary keys 
     *              in the order the columns are to be displayed.  
     *              e.g. $colVars[0] = array('name' => 'Col 1', 'sort' => 'field1');
     *              Leave out the sort column if the column is not to be sorted.  
     *              If not passed in then we assume that columnData and searchableFields
     *              will be set with their respective methods, or with importFieldMap()
     *
     * @access public
     * @return void
     */
    function FF_List($in_defaultSortField, 
                            $in_defaultSortOrder, 
                            $in_defaultDisplayLimit, 
                            $in_persistentData = array(), 
                            $in_columnData = null)
    {
        $this->o_output =& FF_Output::singleton();
        if (!is_null($in_columnData)) {
            $this->setColumnData($in_columnData);
            // set up the default search fields as the column fields, can be overridden later
            $this->setSearchableFields($this->getColumnData());
        }

        // set all the fields with their default values
        $this->persistentData = $in_persistentData;
        $this->setSortField($in_defaultSortField);
        $this->setSortOrder($in_defaultSortOrder);
        $this->setSearchString();
        $this->setSearchField();
        $this->setAdvancedList();
        $this->setPageOffset();
        $this->setDisplayLimit($in_defaultDisplayLimit);
    }

    // }}}
    // {{{ renderSearchBox()

    /**
     * Create a search/navigation box that can be used to search a list or navigate it by
     * page.  Then render the generated form with the template. 
     *
     * @param  string  $in_singularLang Singular description of the data 
     * @param  string  $in_pluralLang Plural description of the data 
     *
     * @access public
     * @return string table for the search box
     */
    function renderSearchBox($in_singularLang, $in_pluralLang)
    {
        // {{{ variable preparation

        // set up sortable fields
        $a_sortFields = array();
        foreach ($this->getColumnData() as $a_val) {
            if (isset($a_val['sort'])) {
                $a_sortFields[$a_val['sort']] = $a_val['name'];
            }
        }

        $a_searchFields = array();
        foreach ($this->getSearchableFields() as $a_val) {
            $a_searchFields[$a_val['search']] = $a_val['name'];
        }
        
        // }}}
        // {{{ quickform preparation

        $a_listVars = $this->getAllListVariables();
        $o_form =& new HTML_QuickForm('search_box', 'POST', FastFrame::selfURL(), '_self');
        $o_form->clearAllTemplates();
        $o_form->setConstants($a_listVars);
        
        // make sure our display displayLimit is not to big so the page will render
        // [!] should be an option [!]
        $s_displayLimitCheck = 'if(document.search_box.displayLimit.value > 100){ document.search_box.displayLimit.value = 100; }';

        // Add all the form elements
        if ($a_listVars['advancedList']) {
            // need to set page offset to one when we search
            $tmp_onclick = ($this->getTotalPages() > 1) ? 
                'document.search_box.pageOffset.options[0].selected = true;' :
                'void(0);';

            $o_form->addElement('text', 'displayLimit', null, array('class' => 'input_content', 'style' => 'vertical-align: middle;', 'size' => 3, 'maxlength' => 3));
            $o_form->addElement('submit', 'displayLimit_submit', _('Update'), array('onclick' => $s_displayLimitCheck, 'style' => 'vertical-align: middle;'));
            $o_form->addElement('select', 'searchField', null, $a_searchFields, array('class' => 'input_content', 'style' => 'vertical-align: text-top;')); 
            $o_form->addElement('text', 'searchString', null, array('size' => 15, 'class' => 'input_content', 'style' => 'vertical-align: text-top;'));
            $o_form->addElement('submit', 'query_submit', _('Search'), array('onclick' => $s_displayLimitCheck . ' ' . $tmp_onclick, 'style' => 'vertical-align: middle;'));
            $o_form->addElement('submit', 'listall_submit', _('List All'), array('onclick' => $s_displayLimitCheck . ' document.search_box.searchString.value = \'\';' . $tmp_onclick, 'style' => 'vertical-align: middle;'));

            $o_form->addElement('select', 'sortOrder', null, array(0 => 'DESC', 1 => 'ASC'), array('class' => 'input_content'));
            $o_form->addElement('select', 'sortField', null, $a_sortFields, array('class' => 'input_content'));
            $o_form->addElement('submit', 'sort_submit', _('Sort'), null, array('onclick' => $s_displayLimitCheck));
        }
        // just make them all hidden if not in the advanced view
        else {
            $o_form->addElement('hidden', 'displayLimit');
            $o_form->addElement('hidden', 'searchField');
            $o_form->addElement('hidden', 'searchString');
            $o_form->addElement('hidden', 'sortOrder');
            $o_form->addElement('hidden', 'sortField');
        }


        // common elements on all pages
        $o_form->addElement('checkbox', 'advancedList', null, _('Advanced List'), array('onclick' => 'if (this.checked) { this.form.submit(); } else if (validate_search_box()) { document.search_box.searchString.value = \'\'; this.form.submit(); } else { return false; }'));

        // common rules
        $o_form->addRule('displayLimit', _('Limit must be an integer'), 'nonzero', null, 'client', true); 

        // add as hidden elements any persistent data
        foreach ($this->persistentData as $s_key => $s_val) {
            $o_form->addElement('hidden', $s_key, $s_val);
        }

        // Construct the menu ring for jumping to different blocks of table
        if ($this->getTotalPages() > 1) {
            foreach (range(1, $this->getTotalPages()) as $i) {
                $a_pageOptions[$i] = sprintf(_('%1$d of %2$d'), $i, $this->getTotalPages());
            }

            // you can't change anything when changing pages...doesn't make sense, so
            // reset the form before going on to the next page
            $o_form->addElement('select', 'pageOffset', null, $a_pageOptions, array('style' => 'vertical-align: middle;', 'onchange' => 'var tmp = this.selectedIndex; this.form.reset(); this.options[tmp].selected = true; if (validate_search_box()) { this.form.submit(); } else { return false; }'));
            $s_pagination = $o_form->renderElement('pageOffset', true);
        }
        else {
            $o_form->addElement('hidden', 'pageOffset');
            $s_pagination = sprintf(_('%1$d of %2$d'), 1, 1) . $o_form->renderElement('pageOffset');
        }

        // }}}
        // {{{ template preparation

        $this->o_output->touchBlock('switch_search_box');
        $s_namespace = $this->o_output->branchBlockNS('SEARCH_BOX', 'search_box', 'genericTable.tpl', 'file');
        $this->o_output->assignBlockCallback(array(&$o_form, 'toHtml'), array(), 'search_box');

        $this->o_output->assignBlockData(
            array(
                'S_TABLE_COLUMNS'        => 3,
                'T_table_header'         => _('Search Results and Options'),
                'S_table_header'         => 'style="text-align: center;"'
            ),
            'search_box'
        );

        $this->o_output->touchBlock($s_namespace . 'switch_table_header');

        // Add the data to the second row
        $this->o_output->assignBlockData(
            array(
                'S_table_field_cell' => 'style="text-align: center; vertical-align: middle; width: 33%;"',
            ),
            $s_namespace . 'table_row'
        );

        $this->o_output->cycleBlock($s_namespace . 'table_content_cell');
        $this->o_output->cycleBlock($s_namespace . 'table_field_cell');

        $this->o_output->assignBlockData(
            array(
                'T_table_field_cell' => sprintf(_('Viewing Page %s'), $s_pagination),
            ),
            $s_namespace . 'table_field_cell'
        );

        $tmp_text = sprintf(
                        _('%1$d Found (%2$d%%), %3$d Listed out of %4$d Total %5$s'),
                        $this->matchedRecords, 
                        $this->getMatchedRecordsPercentage(), 
                        min($this->displayLimit, $this->getDisplayedRecords()),
                        $this->totalRecords, 
                        $this->totalRecords == 1 ? $in_singularLang : $in_pluralLang
                    );

        $this->o_output->assignBlockData(
            array(
                'T_table_field_cell' => $tmp_text,
            ),
            $s_namespace . 'table_field_cell'
        );

        $this->o_output->assignBlockData(
            array(
                'T_table_field_cell' => $o_form->renderElement('advancedList', true),
                'S_table_field_cell' => 'style="white-space: nowrap"',
            ),
            $s_namespace . 'table_field_cell'
        );

        // Add the data to the first row
        if ($this->getAdvancedList()) {
            // assigns attributes to all cells in the row
            $this->o_output->assignBlockData(
                array(
                    'S_table_content_cell' => 'style="text-align: center; vertical-align: middle; width: 33%; white-space: nowrap;"',
                ),
                $s_namespace . 'table_row'
            );

            $this->o_output->cycleBlock($s_namespace . 'table_content_cell');
            
            $tmp_text = sprintf(
                            _('%1$s rows per page %2$s'), 
                            $o_form->renderElement('displayLimit', true), 
                            $o_form->renderElement('displayLimit_submit', true)
                        );

            $this->o_output->assignBlockData(
                array(
                    'T_table_content_cell' => $tmp_text,
                ), 
                $s_namespace . 'table_content_cell'
            );

            $tmp_help = _('Find items in the list by entering a search term in the box to the right.  If you want to only search a particular field then select it from the drop down list.  To search between two dates you can enter the dates in the following format: mm/dd/yyyy - mm/dd/yyyy');
            $tmp_text = sprintf(
                            _('%1$s %2$s in %3$s'), 
                            $this->o_output->link(
                                'javascript: void(0);',
                                _('Find'),
                                array('title' => $tmp_help, 'caption' => _('Search Help'), 'status' => _('Search Help'))
                            ),
                            $o_form->renderElement('searchString', true), 
                            $o_form->renderElement('searchField', true) . ' ' .
                            $o_form->renderElement('query_submit', true) . ' ' .
                            $o_form->renderElement('listall_submit', true)
                        );

            $this->o_output->assignBlockData(
                array(
                    'T_table_content_cell' => $tmp_text,
                ), 
                $s_namespace . 'table_content_cell'
            );

            $this->o_output->assignBlockData(
                array(
                    'T_table_content_cell' => $o_form->renderElement('sortOrder', true) . ' ' .
                                              $o_form->renderElement('sortField', true) . ' ' .
                                              $o_form->renderElement('sort_submit', true),
                ),
                $s_namespace . 'table_content_cell'
            );
        }

        // }}}
    }

    // }}}
    // {{{ generateNavigationLinks()

    /**
     * Generates first, previous, next, and last links with images for navigation through
     * the multiple pages of data.
     *
     * @param bool $in_renderLinks (optional) Render the links in the template?  Otherwise we
     *                          just return an array of the links.
     *
     * @access public
     * @return mixed Void if rendering, otherwise an array of the navigation links. 
     */
    function generateNavigationLinks($in_renderLinks = true)
    {
        // language constructs used for navigation
        $lang_firstPage = array('title' => _('First'),    'status' => _('Jump to First Page'));
        $lang_nextPage  = array('title' => _('Next'),     'status' => _('Jump to Next Page'));
        $lang_prevPage  = array('title' => _('Previous'), 'status' => _('Jump to Previous Page'));
        $lang_lastPage  = array('title' => _('Last'),     'status' => _('Jump to Last Page'));
        $lang_atFirst   = array('title' => _('Disabled'), 'status' => _('Already at First Page'));
        $lang_atLast    = array('title' => _('Disabled'), 'status' => _('Already at Last Page'));
      
        $a_urlVars = array_merge($this->persistentData, $this->getAllListVariables());
        // set up the four actions
        $a_navigation = array();
        $a_navigation['first']    = $this->getPageOffset() > 1 ? 
                                    $this->o_output->link(
                                        FastFrame::selfURL($a_urlVars, array('pageOffset' => $this->getPageID('first'))), 
                                        $this->o_output->imgTag('first.gif', 'arrows'), 
                                        $lang_firstPage
                                    ) : 
                                    $this->o_output->imgTag('first-gray.gif', 'arrows', $lang_atFirst);
        $a_navigation['previous'] = $this->getPageOffset() > 1 ?
                                    $this->o_output->link(
                                        FastFrame::selfURL($a_urlVars, array('pageOffset' => $this->getPageID('previous'))), 
                                        $this->o_output->imgTag('prev.gif', 'arrows'), 
                                        $lang_prevPage
                                    ) : 
                                    $this->o_output->imgTag('prev-gray.gif', 'arrows', $lang_atFirst);
        $a_navigation['next']     = $this->getPageOffset() < $this->getPageID('last') ? 
                                    $this->o_output->link(
                                        FastFrame::selfURL($a_urlVars, array('pageOffset' => $this->getPageID('next'))), 
                                        $this->o_output->imgTag('next.gif', 'arrows'), 
                                        $lang_nextPage
                                    ) : 
                                    $this->o_output->imgTag('next-gray.gif', 'arrows', $lang_atLast);
        $a_navigation['last']     = $this->getPageOffset() < $this->getPageID('last') ? 
                                    $this->o_output->link(
                                        FastFrame::selfURL($a_urlVars, array('pageOffset' => $this->getPageID('last'))), 
                                        $this->o_output->imgTag('last.gif', 'arrows'), 
                                        $lang_lastPage
                                    ) : 
                                    $this->o_output->imgTag('last-gray.gif', 'arrows', $lang_atLast);
        
        if ($in_renderLinks) {
            $this->o_output->assignBlockData(
                array(
                    'I_navigation_first'    => $a_navigation['first'],
                    'I_navigation_previous' => $a_navigation['previous'],
                    'I_navigation_next'     => $a_navigation['next'],
                    'I_navigation_last'     => $a_navigation['last'],
                ),
                FASTFRAME_TEMPLATE_GLOBAL_BLOCK,
                false
            );
        }
        else {
            return $a_navigation;
        }
    }

    // }}}
    // {{{ generateSortFields()

    /**
     * Generate a set of column headers which are HTML'd to have images and links so they
     * can be clicked on to sort the page.
     *
     * @param string $in_tableNamespace (optional) The namespace for the table to render
     *               the cells to.  If not passed in then we just return the array of sort
     *               fields.  It is up to you to branch the block, and assign
     *               global table variables such as S_TABLE_COLUMNS
     *
     * @return mixed Either nothing (we render the cells) or an array of the sort fields 
     */
    function generateSortFields($in_tableNamespace = null)
    {
        foreach ($this->getColumnData() as $a_colData) {
            // check to see if it is searchable, and if so build the link
            if (!empty($a_colData['sort'])) {
                // if we previously sorted on this field, reverse the sort direction
                if ($this->getSortField() == $a_colData['sort']) {
                    $tmp_sort = $this->getSortOrder() ? 0 : 1;
                }
                // this is a new column sort, default sort
                else {
                    $tmp_sort = $this->getSortOrder();
                }

                // modify the two get variables for this sort
                $a_listVars = array_merge($this->persistentData, $this->getAllListVariables(), array('sortField' => $a_colData['sort'], 'sortOrder' => $tmp_sort));

                $tmp_title = sprintf(
                                _('Sort %1$s (%2$s)'), 
                                $a_colData['name'], 
                                $tmp_sort ? _('Ascending') : _('Descending')
                            ); 

                $tmp_href = $this->o_output->link(
                    FastFrame::selfURL($a_listVars), 
                    $a_colData['name'],
                    array(
                        'title' => $tmp_title, 
                    )
                );

                // see if we should display an arrow 
                if ($this->getSortField() == $a_colData['sort']) {
                    // Determine which image to display
                    $tmp_img = $this->getSortOrder() ? 
                        $this->o_output->imgTag('up.gif', 'arrows', array('align' => 'middle')) : 
                        $this->o_output->imgTag('down.gif', 'arrows', array('align' => 'middle'));
                    $tmp_href .= ' ' . $this->o_output->link(FastFrame::selfURL($a_listVars), $tmp_img, array('title' => $tmp_title)); 
                }
                
                $a_fieldCells[] = $tmp_href;
            }
            else {
                $a_fieldCells[] = $a_colData['name'];
            }
        }

        // now render the sort fields if that was specified
        if (!is_null($in_tableNamespace)) {
            $this->o_output->touchBlock($in_tableNamespace . 'table_row');
            $this->o_output->cycleBlock($in_tableNamespace . 'table_field_cell');
            $this->o_output->cycleBlock($in_tableNamespace . 'table_content_cell');
            foreach ($a_fieldCells as $s_cell) {
                $this->o_output->assignBlockData(
                    array(
                        'T_table_field_cell' => $s_cell,
                    ),
                    $in_tableNamespace . 'table_field_cell'
                );
            }
        }
        else {
            return $a_fieldCells;
        }
    }

    // }}}
    // {{{ getPageID()

    /**
     * Calculates one of the four available page ID's.
     *
     * @param string $in_type What type of page id to get.  Available options are: 'first',
     *                        'previous', 'next', 'last'
     *
     * @access public
     * @return int The page ID
     */
    function getPageID($in_type)
    {
        switch ($in_type) {
            case 'first':
                return 1;
            break;
            case 'previous':
                return max(1, $this->getPageOffset() - 1);
            break;
            case 'next':
                return min($this->getTotalPages(), $this->getPageOffset() + 1);
            break;
            case 'last':
                return $this->getTotalPages();
            break;
            default:
                return 1;
            break;
        }
    }

    // }}}
    // {{{ getAdvancedList()

    /**
     * Gets the advanced list variable.
     *
     * @access public
     * @return bool The advancedList variable.
     */
    function getAdvancedList()
    {
        return $this->advancedList;
    }

    // }}}
    // {{{ setAdvancedList()

    /**
     * Sets the advanced list variable from $in_value or GET/POST if not passed in,
     * defaulting to false if value is not present.
     *
     * @param bool $in_value (optional) Should this be an advanced list or not?
     *
     * @access public
     * @return void
     */
    function setAdvancedList($in_value = null)
    {
        if (is_null($in_value)) {
            // if it's a form submit then take whatever they choose
            if (FastFrame::getCGIParam('pageOffset', 'gp', false) !== false) {
                $this->advancedList = (bool) FastFrame::getCGIParam('advancedList', 'gp', false);
            }
            // otherwise take their default from session 
            else {
                $this->advancedList = (bool) FastFrame::getCGIParam('advancedList', 's', false);
            }
        }
        else {
            $this->advancedList = (bool) $in_value;
        }
    }

    // }}}
    // {{{ getDisplayLimit()

    /**
     * Gets the display limit variable.
     *
     * @access public
     * @return int The displayLimit variable.
     */
    function getDisplayLimit()
    {
        return $this->displayLimit;
    }

    // }}}
    // {{{ setDisplayLimit()

    /**
     * Sets the display limit variable from GET/POST/SESSION or if that is not present falls
     * back onto passed in value
     *
     * @param int $in_limit Fallback display limit per page. 
     *
     * @access public
     * @return void
     */
    function setDisplayLimit($in_limit)
    {
        $this->displayLimit = (int) abs(FastFrame::getCGIParam('displayLimit', 'gps', $in_limit));
    }

    // }}}
    // {{{ getPageOffset()

    /**
     * Gets the page offset variable.
     *
     * @access public
     * @return int The pageOffset variable.
     */
    function getPageOffset()
    {
        return $this->pageOffset;
    }

    // }}}
    // {{{ setPageOffset()

    /**
     * Sets the page offset variable from $in_value or GET/POST/SESSION if not passed in,
     * defaulting to 1 if not present.
     *
     * @param int $in_value (optional) What page to show. 
     *
     * @access public
     * @return void
     */
    function setPageOffset($in_value = null)
    {
        if (is_null($in_value)) {
            $this->pageOffset = (int) abs(FastFrame::getCGIParam('pageOffset', 'gps', 1));  
        }
        else {
            $this->pageOffset = (int) $in_value;
        }
    }

    // }}}
    // {{{ getRecordOffset()

    /**
     * Calculates the record offset.
     *
     * @access public
     * @return int The record offset
     */
    function getRecordOffset()
    {
        return $this->getDisplayLimit() ? 
               ($this->getPageOffset() - 1) * $this->getDisplayLimit() : 
               0;
    }

    // }}}
    // {{{ getSortOrder()

    /**
     * Gets the sort order variable. 1 for ASC and 0 for DESC.
     *
     * @access public
     * @return int The sortOrder variable.
     */
    function getSortOrder()
    {
        return $this->sortOrder;
    }

    // }}}
    // {{{ setSortOrder()

    /**
     * Sets the display limit variable from GET/POST/SESSION or if that is not present falls
     * back onto passed in value
     *
     * @param int $in_sort What order to sort the data. 1 for ASC, 0 for DESC
     *
     * @access public
     * @return void
     */
    function setSortOrder($in_sort)
    {
        $this->sortOrder = (int) FastFrame::getCGIParam('sortOrder', 'gps', $in_sort);
    }

    // }}}
    // {{{ getSortField()

    /**
     * Gets the sort field variable.
     *
     * @access public
     * @return string The sortField variable.
     */
    function getSortField()
    {
        return $this->sortField;
    }

    // }}}
    // {{{ setSortField()

    /**
     * Sets the sort field variable from GET/POST/SESSION or $in_value if that is not
     * present
     *
     * @param string $in_field The fallback field to sort on
     *
     * @access public
     * @return void
     */
    function setSortField($in_field)
    {
        $this->sortField = FastFrame::getCGIParam('sortField', 'gps', $in_field);
    }

    // }}}
    // {{{ getSearchString()

    /**
     * Gets the search string variable.
     *
     * @access public
     * @return string The searchString variable.
     */
    function getSearchString()
    {
        return $this->searchString;
    }

    // }}}
    // {{{ setSearchString()

    /**
     * Sets the search string variable from $in_value or GET/POST if not passed in,
     * defaulting to empty string if not present.
     *
     * @param string $in_value (optional) What string to search for. 
     *
     * @access public
     * @return void
     */
    function setSearchString($in_value = null)
    {
        if (is_null($in_value)) {
            $this->searchString = FastFrame::getCGIParam('searchString', 'gp', '');
        }
        else {
            $this->searchString = $in_value;
        }
    }

    // }}}
    // {{{ getSearchField()

    /**
     * Gets the search field variable.
     *
     * @access public
     * @return string The searchField variable.
     */
    function getSearchField()
    {
        return $this->searchField;
    }

    // }}}
    // {{{ setSearchField()

    /**
     * Sets the search field variable from $in_value or GET/POST if not passed in,
     * defaulting to empty field if not present.
     *
     * @param string $in_value (optional) What field to search on. 
     *
     * @access public
     * @return void
     */
    function setSearchField($in_value = null)
    {
        if (is_null($in_value)) {
            $this->searchField = FastFrame::getCGIParam('searchField', 'gp', '');
        }
        else {
            $this->searchField = $in_value;
        }
    }

    // }}}
    // {{{ setSearchableFields()

    /**
     * Sets the searchable fields.
     *
     * @param array $in_fields An array of searchable fields and the name for those fields.
     *              If the search attribute is not set the field will be skipped.
     *              e.g. $searchFields[0] = array('name' => 'Search Field', 'search' => 'field1');
     *              The data format for column data can also be passed to make it easy to
     *              search on the same fields that are sorted.
     * @param bool $in_addAllFields (optional) Add an 'All Fields' setting at the beginning
     *              (with a special sort key of **all**) which can be used by the app to
     *              search all fields.
     *
     * @access public
     * @return void
     */
    function setSearchableFields($in_fields, $in_addAllFields = false)
    {
        $this->searchableFields = array();
        if ($in_addAllFields) {
            $this->searchableFields[] = array('name' => _('All Fields'), 'search' => $this->getAllFieldsKey());
        }

        foreach ($in_fields as $a_val) {
            if (isset($a_val['search'])) {
                $this->searchableFields[] = $a_val;
            }

            // this allows for the column data structure
            if (isset($a_val['sort'])) {
                $this->searchableFields[] = array('name' => $a_val['name'], 'search' => $a_val['sort']);
            }
        }
    }

    // }}}
    // {{{ getSearchableFields()

    /**
     * Gets the searchable fields.
     *
     * @param bool $in_excludeAll (optional) Exclude the All Fields key from the list?
     *
     * @access public
     * @return array An array of the searchable fields 
     */
    function getSearchableFields($in_excludeAll = false)
    {
        if ($in_excludeAll) {
            $a_list = array(); 
            foreach ($this->searchableFields as $a_val) {
                if ($a_val['search'] != $this->getAllFieldsKey()) {
                    $a_list[] = $a_val;
                }
            }

            return $a_list;
        }
        else {
            return $this->searchableFields;
        }
    }

    // }}}
    // {{{ getColumnData()

    /**
     * Gets the column data. 
     *
     * @access public
     * @return array The column data 
     */
    function getColumnData()
    {
        return $this->columnData;
    }

    // }}}
    // {{{ setColumnData()

    /**
     * Sets the column data. 
     *
     * @param array $in_columnData A multidimensional array with the primary keys in the order
     *              the columns are to be displayed.  
     *              e.g. $colVars[0] = array('name' => 'Col 1', 'sort' => 'field1');
     *              Leave out the sort column if the column is not to be sorted.
     *
     * @access public
     * @return void
     */
    function setColumnData($in_columnData)
    {
        $this->columnData = (array) $in_columnData;
    }

    // }}}
    // {{{ setTotalRecords()

    /**
     * Sets the total records variable 
     *
     * @param int $in_totalRecords The total number of records in the data set.
     *
     * @access public
     * @return void
     */
    function setTotalRecords($in_totalRecords)
    {
        $this->totalRecords = $in_totalRecords;
    }

    // }}}
    // {{{ setMatchedRecords()

    /**
     * Sets the matched records variable 
     *
     * @param int $in_matchedRecords The total number of matched records in the data set.
     *
     * @access public
     * @return void
     */
    function setMatchedRecords($in_matchedRecords)
    {
        $this->matchedRecords = $in_matchedRecords;
    }

    // }}}
    // {{{ setDisplayedRecords()

    /**
     * Sets the displayed records variable 
     *
     * @param int $in_displayedRecords The total number of records displayed.
     *
     * @access public
     * @return void
     */
    function setDisplayedRecords($in_displayedRecords)
    {
        $this->displayedRecords = $in_displayedRecords;
    }

    // }}}
    // {{{ getDisplayedRecords()

    /**
     * Gets the displayed records variable 
     *
     * @access public
     * @return int The number of displayed records. 
     */
    function getDisplayedRecords()
    {
        return $this->displayedRecords;
    }

    // }}}
    // {{{ getAllListVariables()

    /**
     * Returns an array of all list variables that need to be passed on from page to page
     * when using a list.
     *
     * @access public
     * @return array An array of the list variables passed on by GET/POST
     */
    function getAllListVariables()
    {
        $a_vars = array();
        $a_vars['sortField'] = $this->getSortField();
        $a_vars['sortOrder'] = $this->getSortOrder();
        $a_vars['sortField'] = $this->getSortField();
        $a_vars['searchString'] = $this->getSearchString();
        $a_vars['searchField'] = $this->getSearchField();
        $a_vars['advancedList'] = $this->getAdvancedList();
        $a_vars['pageOffset'] = $this->getPageOffset();
        $a_vars['displayLimit'] = $this->getDisplayLimit();
        return $a_vars;
    }

    // }}}
    // {{{ getMatchedRecordsPercentage()

    /**
     * Calculates the percentage of records matched. 
     *
     * @access public
     * @return float The percentage of matched records 
     */
    function getMatchedRecordsPercentage()
    {
        if ($this->totalRecords > 0) {
            return round(($this->matchedRecords / $this->totalRecords) * 100, 2);
        }
        else {
            return 0;
        }
    }

    // }}}
    // {{{ getTotalPages()

    /**
     * Calculates the total number of pages are in the data set. 
     *
     * @access public
     * @return int The total number of pages. 
     */
    function getTotalPages()
    {
        return ceil($this->matchedRecords / $this->displayLimit);
    }

    // }}}
    // {{{ getAllFieldsKey()

    /**
     * Get the allFieldsKey
     *
     * @access public
     * @return string The key used for searching all fields 
     */
    function getAllFieldsKey()
    {
        return $this->allFieldsKey;
    }

    // }}}
}
?>
