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

require_once dirname(__FILE__) . '/Output.php';
require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/Renderer/QuickHtml.php';

// }}}
// {{{ constants

define('SEARCH_BOX_SIMPLE', 1);
define('SEARCH_BOX_NORMAL', 2);
define('SEARCH_BOX_ADVANCED', 3);

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
     * @var object
     */
    var $o_output;

    /**
     * The column data. 
     * @var array
     */
    var $columnData = array();

    /**
     * The searchable fields. 
     * @var array
     */
    var $searchableFields = array();

    /**
     * The key for searching all fields
     * @var string
     */
    var $allFieldsKey = '**all**'; 

    /**
     * The total number of records in the data set 
     * @var int
     */
    var $totalRecords = 0;

    /**
     * The total number of matched records in the data set 
     * @var int
     */
    var $matchedRecords = 0;

    /**
     * The current sort field 
     * @var string
     */
    var $sortField;

    /**
     * The current sort order (1 = ASC, 0 = DESC)
     * @var int
     */
    var $sortOrder;

    /**
     * The current search string 
     * @var int
     */
    var $searchString;

    /**
     * The current field being searched 
     * @var int
     */
    var $searchField;

    /**
     * Type of search box
     * @var int
     */
    var $searchBoxType;

    /**
     * The current page offset 
     * @var int
     */
    var $pageOffset;

    /**
     * The current limit of items per page
     * @var int
     */
    var $displayLimit;

    /**
     * Any persistent data which should be passed as hidden fields (i.e. actionId) 
     * @var array
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
    function FF_List($in_defaultSortField, $in_defaultSortOrder, 
            $in_defaultDisplayLimit, $in_persistentData = array(), 
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
        $this->setSearchBoxType();
        $this->setPageOffset();
        $this->setDisplayLimit($in_defaultDisplayLimit);
    }

    // }}}
    // {{{ renderSearchBox()

    /**
     * Create a search/navigation box that can be used to search a list or navigate it by
     * page.  Then render the generated form using the genericTable widget. 
     *
     * @param string $in_singularLang Singular description of the data 
     * @param string $in_pluralLang Plural description of the data 
     *
     * @access public
     * @return string The html for the search table 
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
        $o_renderer =& new HTML_QuickForm_Renderer_QuickHtml($o_form);
        $o_form->setConstants($a_listVars);
        
        // Need to set page offset to one when we search or change limit
        $tmp_onclick = ($this->getTotalPages() > 1) ? 
            'document.search_box.pageOffset.options[0].selected = true;' :
            'void(0);';

        // Add form elements which go only on advanced list in normal mode
        if ($a_listVars['searchBoxType'] == SEARCH_BOX_ADVANCED) {
            $o_form->addElement('text', 'displayLimit', null, 
                    array('style' => 'vertical-align: middle;', 'size' => 3, 'maxlength' => 3));
            $o_form->addElement('submit', 'displayLimit_submit', _('Update'), 
                    array('style' => 'vertical-align: middle;', 'onclick' => $tmp_onclick));
            $o_form->addElement('select', 'sortOrder', null, array(0 => 'DESC', 1 => 'ASC'));
            $o_form->addElement('select', 'sortField', null, $a_sortFields);
            $o_form->addElement('submit', 'sort_submit', _('Sort'));
        }
        else {
            $o_form->addElement('hidden', 'displayLimit');
            $o_form->addElement('hidden', 'sortOrder');
            $o_form->addElement('hidden', 'sortField');
        }

        // Add form elements which go in advanced list or simple mode
        if ($a_listVars['searchBoxType'] == SEARCH_BOX_ADVANCED ||
            $a_listVars['searchBoxType'] == SEARCH_BOX_SIMPLE) {
            $o_form->addElement('select', 'searchField', null, $a_searchFields, 
                    array('style' => 'vertical-align: text-top;')); 
            $o_form->addElement('text', 'searchString', null, 
                    array('size' => 15, 'style' => 'vertical-align: text-top;', 'onfocus' => 'this.value = ""'));
            $o_form->addElement('submit', 'query_submit', _('Search'), 
                    array('onclick' => $tmp_onclick, 'style' => 'vertical-align: middle;'));
            $o_form->addElement('submit', 'listall_submit', _('List All'), 
                    array('onclick' => 'document.search_box.searchString.value = \'\';' . $tmp_onclick, 
                        'style' => 'vertical-align: middle;'));
        }
        else {
            $o_form->addElement('hidden', 'searchField');
            $o_form->addElement('hidden', 'searchString');
        }

        if ($a_listVars['searchBoxType'] != SEARCH_BOX_SIMPLE) {
            $o_form->addElement('advcheckbox', 'searchBoxType', null, _('Advanced List'), 
                    array('onclick' => 'if (this.checked) { this.form.submit(); } else if (validate_search_box()) { document.search_box.searchString.value = \'\'; this.form.submit(); } else { return false; }'),
                    array(SEARCH_BOX_NORMAL, SEARCH_BOX_ADVANCED));
        }

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

            // You can't change anything when changing pages...doesn't make sense, so
            // reset the form before going on to the next page
            $o_form->addElement('select', 'pageOffset', null, $a_pageOptions, 
                    array('style' => 'vertical-align: middle;', 'onchange' => 'var tmp = this.selectedIndex; this.form.reset(); this.options[tmp].selected = true; if (validate_search_box()) { this.form.submit(); } else { return false; }'));
            $s_pagination = $o_renderer->elementToHtml('pageOffset');
        }
        else {
            $o_form->addElement('hidden', 'pageOffset');
            $s_pagination = sprintf(_('%1$d of %2$d'), 1, 1) . $o_renderer->elementToHtml('pageOffset');
        }

        // }}}
        // {{{ template preparation

        if ($a_listVars['searchBoxType'] == SEARCH_BOX_SIMPLE) {
            $o_searchWidget =& $this->o_output->getWidgetObject('searchTableSimple');
        }
        else {
            $o_searchWidget =& $this->o_output->getWidgetObject('searchTable');
        }

        $s_foundText = sprintf(_('%1$d Found (%2$d%%), %3$d Listed out of %4$d Total %5$s'), 
                $this->getMatchedRecords(), 
                $this->getMatchedRecordsPercentage(), 
                min(($this->getMatchedRecords() - $this->getRecordOffset()), 
                    $this->getDisplayLimit(), $this->getMatchedRecords()),
                $this->totalRecords, 
                $this->totalRecords == 1 ? $in_singularLang : $in_pluralLang);

        $o_searchWidget->assignBlockData(
                array(
                    'T_search_header' => _('Search Options'),
                    'T_search_viewing' => sprintf(_('Viewing Page %s'), $s_pagination),
                    'T_search_found' => $s_foundText), 
                $this->o_output->getGlobalBlockName());

        if ($a_listVars['searchBoxType'] != SEARCH_BOX_SIMPLE) {
            $s_printLink = $this->o_output->link(
                    FastFrame::selfURL($this->persistentData, $this->getAllListVariables(), 
                        array('printerFriendly' => 1)), 
                    _('Printer Friendly'));

            $o_searchWidget->assignBlockData(
                array('T_search_options' => $o_renderer->elementToHtml('searchBoxType') . ' | ' . $s_printLink),
                $this->o_output->getGlobalBlockName());
        }

        if ($a_listVars['searchBoxType'] == SEARCH_BOX_ADVANCED) {
            $s_limitText = sprintf(_('%1$s rows per page %2$s'), 
                    $o_renderer->elementToHtml('displayLimit'), 
                    $o_renderer->elementToHtml('displayLimit_submit'));

            $s_sortText = $o_renderer->elementToHtml('sortOrder') . ' ' .
                          $o_renderer->elementToHtml('sortField') . ' ' .
                          $o_renderer->elementToHtml('sort_submit');

            $o_searchWidget->assignBlockData(
                    array('T_search_limit' => $s_limitText, 'T_search_sort' => $s_sortText), 
                    $this->o_output->getGlobalBlockName());
        }

        if ($a_listVars['searchBoxType'] != SEARCH_BOX_NORMAL) {
            $tmp_help = _('Find items in the list by entering a search term in the box to the right.  If you want to only search a particular field then select it from the drop down list.  To search between two dates you can enter the dates in the following format: mm/dd/yyyy - mm/dd/yyyy');
            $s_findText = sprintf(_('%1$s %2$s %3$s in %4$s'), 
                    $this->o_output->getHelpLink($tmp_help, _('Search Help')), 
                    _('Find'), 
                    $o_renderer->elementToHtml('searchString'), 
                    $o_renderer->elementToHtml('searchField') . ' ' .
                    $o_renderer->elementToHtml('query_submit') . ' ' .
                    $o_renderer->elementToHtml('listall_submit'));

            $o_searchWidget->assignBlockData(
                    array('T_search_find' => $s_findText),
                    $this->o_output->getGlobalBlockName());
        }

        $o_form->accept($o_renderer);
        $o_searchWidget->assignBlockCallback(array(&$o_renderer, 'toHtml'));
        return $o_searchWidget->render();

        // }}}
    }

    // }}}
    // {{{ generateNavigationLinks()

    /**
     * Generates first, previous, next, and last links with images for navigation through
     * the multiple pages of data.
     *
     * @access public
     * @return array An array of the navigation links. 
     */
    function generateNavigationLinks()
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
                                            FastFrame::selfURL($a_urlVars, array(
                                                    'pageOffset' => $this->getPageID('first'))), 
                                            $this->o_output->imgTag('first.gif', 'arrows'), 
                                            $lang_firstPage) : 
                                    $this->o_output->imgTag('first-gray.gif', 'arrows', $lang_atFirst);

        $a_navigation['previous'] = $this->getPageOffset() > 1 ?
                                    $this->o_output->link(
                                            FastFrame::selfURL($a_urlVars, array(
                                                    'pageOffset' => $this->getPageID('previous'))), 
                                            $this->o_output->imgTag('prev.gif', 'arrows'), 
                                            $lang_prevPage) : 
                                    $this->o_output->imgTag('prev-gray.gif', 'arrows', $lang_atFirst);

        $a_navigation['next']     = $this->getPageOffset() < $this->getPageID('last') ? 
                                    $this->o_output->link(
                                            FastFrame::selfURL($a_urlVars, array(
                                                    'pageOffset' => $this->getPageID('next'))), 
                                            $this->o_output->imgTag('next.gif', 'arrows'), 
                                            $lang_nextPage) : 
                                    $this->o_output->imgTag('next-gray.gif', 'arrows', $lang_atLast);

        $a_navigation['last']     = $this->getPageOffset() < $this->getPageID('last') ? 
                                    $this->o_output->link(
                                            FastFrame::selfURL($a_urlVars, array(
                                                    'pageOffset' => $this->getPageID('last'))), 
                                            $this->o_output->imgTag('last.gif', 'arrows'), 
                                            $lang_lastPage) : 
                                    $this->o_output->imgTag('last-gray.gif', 'arrows', $lang_atLast);
        
        return $a_navigation;
    }

    // }}}
    // {{{ generateSortFields()

    /**
     * Generate a set of column headers which are HTML'd to have images and links so they
     * can be clicked on to sort the page.
     *
     * @return array An array of the sort fields with the links and descriptions of the cell
     */
    function generateSortFields()
    {
        foreach ($this->getColumnData() as $a_colData) {
            // Check to see if it is searchable and we're not in print mode.
            // If so build a link to sort on the column
            if (!empty($a_colData['sort']) && 
                !FastFrame::getCGIParam('printerFriendly', 'gp', false)) {
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

        return $a_fieldCells;
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
    // {{{ getSearchBoxOptions()

    /**
     * Gets an array of the search box options.
     *
     * @access public
     * @return array An array of id => description
     */
    function getSearchBoxOptions()
    {
        return array(
                SEARCH_BOX_SIMPLE => _('Simple'), 
                SEARCH_BOX_NORMAL => _('Normal'), 
                SEARCH_BOX_ADVANCED => _('Advanced'));
    }

    // }}}
    // {{{ getSearchBoxType()

    /**
     * Gets the search box type.
     *
     * @access public
     * @return int The searchBoxType variable.
     */
    function getSearchBoxType()
    {
        return $this->searchBoxType;
    }

    // }}}
    // {{{ setSearchBoxType()

    /**
     * Sets the search box type variable from $in_value or GET/POST/SESSION if not passed
     * in, defaulting to SEARCH_BOX_NORMAL if value is not present.
     *
     * @param int $in_value (optional) Search box type. 
     *
     * @access public
     * @return void
     */
    function setSearchBoxType($in_value = null)
    {
        if (is_null($in_value)) {
            $this->searchBoxType = FastFrame::getCGIParam('searchBoxType', 'gps', SEARCH_BOX_NORMAL);
        }
        else {
            $this->searchBoxType = $in_value;
        }

        // Make sure it is valid type
        $a_types = $this->getSearchBoxOptions();
        if (!isset($a_types[$this->searchBoxType])) {
            $this->searchBoxType = SEARCH_BOX_NORMAL;
        }

        $_SESSION['searchBoxType'] = $this->searchBoxType;
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
        $_SESSION['displayLimit'] = $this->displayLimit;
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
            $this->searchField = FastFrame::getCGIParam('searchField', 'gp', $this->getAllFieldsKey());
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
    // {{{ getMatchedRecords()

    /**
     * Gets the matched records variable 
     *
     * @access public
     * @return int The matched number of records
     */
    function getMatchedRecords()
    {
        return $this->matchedRecords;
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
        $a_vars['searchBoxType'] = $this->getSearchBoxType();
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
            return round(($this->getMatchedRecords() / $this->totalRecords) * 100, 2);
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
        return ceil($this->getMatchedRecords() / $this->getDisplayLimit());
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
