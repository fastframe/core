<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2004 The Codejanitor Group                        |
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
define('SEARCH_BOX_ADVANCED', 3);
define('SEARCH_BOX_ONLYSEARCH', 4);

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
     * The array of extra search elements for the search box
     * @var array
     */
    var $extraSearchElements = array();

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
     * The id of the current list
     * @var string
     */
    var $listId;

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
     * Whether or not to show the list all button
     * @var bool
     */
    var $listAll = true;

    /**
     * Whether or not the list type is locked
     * @var bool
     */
    var $typeLocked = false;

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
     * @param string $in_listId The id for this list.  Used to keep track of session
     *               variables (e.g. displayLimit, searchString) for each specific list.
     * @param string $in_sortField The default field to sort on
     * @param int $in_sortOrder The default sort order (1 = ASC, 0 = DESC)
     * @param int $in_displayLimit The default display limit
     *
     * @access public
     * @return void
     */
    function FF_List($in_listId, $in_sortField = null, $in_sortOrder = null, $in_displayLimit = null)
    {
        $this->o_output =& FF_Output::singleton();
        $this->listId = $in_listId;
        // These variables can change the number of results returned,
        // and thus could leave the user on an invalid page.  So, if we
        // are on a list page and they have changed we set the page to 1.
        if (FF_Request::getParam("searchString[$this->listId]", 'gp', false) !== false &&
            (FF_Request::getParam("searchString[$this->listId]", 'gp') !=
                FF_Request::getParam("searchString[$this->listId]", 's') ||
             FF_Request::getParam("searchField[$this->listId]", 'gp') !=
                FF_Request::getParam("searchField[$this->listId]", 's') ||
             FF_Request::getParam("displayLimit[$this->listId]", 'gp') !=
                FF_Request::getParam("displayLimit[$this->listId]", 's'))) {
            FF_Request::setParam("pageOffset[$this->listId]", 1, 'gp');
        }

        // Set all the fields with their default values
        $this->setSortField($in_sortField);
        $this->setSortOrder($in_sortOrder);
        $this->setSearchString();
        $this->setSearchField();
        $this->setSearchBoxType();
        $this->setPageOffset();
        $this->setDisplayLimit($in_displayLimit);
    }

    // }}}
    // {{{ renderSearchBox()

    /**
     * Create a search/navigation box that can be used to search a list
     * or navigate it by page.  Then render the generated form using the
     * searchTable widget.
     *
     * @param string $in_pluralText The plural form of what is being searched
     * @param bool $in_focusSearch Focus the search box?
     *
     * @access public
     * @return string The html for the search table 
     */
    function renderSearchBox($in_pluralText, $in_focusSearch)
    {
        // {{{ quickform preparation

        $a_listVars = $this->getAllListVariables();
        $o_form =& new HTML_QuickForm('search_box', 'POST', FastFrame::selfURL(), '_self');
        $o_renderer =& new HTML_QuickForm_Renderer_QuickHtml();
        $o_form->setConstants(array_merge($this->persistentData, $a_listVars));
        $a_searchFields = array();
        foreach ($this->getSearchableFields() as $a_val) {
            $a_searchFields[$a_val['search']] = $a_val['name'];
        }
        
        $s_numListed = min(($this->getMatchedRecords() - $this->getRecordOffset()), 
                $this->getDisplayLimit(), $this->getMatchedRecords());

        // Add form elements which go only on advanced list
        if ($a_listVars['searchBoxType'] == SEARCH_BOX_ADVANCED) {
            $o_form->addElement('text', "displayLimit[$this->listId]", null, 
                    array('style' => 'vertical-align: middle; width: 15px;', 'size' => 2, 'maxlength' => 3));
            $o_form->addRule("displayLimit[$this->listId]", _('Limit must be a positive integer'), 
                    'nonzero', null, 'client', true); 

            $o_form->addElement('select', "searchField[$this->listId]", null, $a_searchFields, 
                    array('style' => 'vertical-align: middle;'));

            // Construct the menu ring for jumping to different blocks of table
            if ($this->getTotalPages() > 1) {
                // Only want the ring to go 10 before and behind so it
                // doesn't get too big with large datasets
                $s_start = max($this->getPageOffset() - 10, 1);
                $s_end = min($this->getPageOffset() + 10, $this->getTotalPages());
                for ($i = $s_start; $i <= $s_end; $i++) {
                    $a_pageOptions[$i] = sprintf(_('%1$d of %2$d'), $i, $this->getTotalPages());
                }

                // You can't change anything when changing pages...doesn't make sense, so
                // reset the form before going on to the next page
                $o_form->addElement('select', "pageOffset[$this->listId]", null, $a_pageOptions, 
                        array('style' => 'vertical-align: middle;', 'onchange' => 'var tmp = this.selectedIndex; this.form.reset(); this.options[tmp].selected = true; if (typeof(validate_search_box) != "function" || validate_search_box(this.form)) { this.form.submit(); } else { return false; }'));
                // Has to be rendered later
                $s_pagination = false; 
            }
            else {
                $o_form->addElement('hidden', "pageOffset[$this->listId]");
                $s_pagination = _('1 of 1');
            }
        }
        else {
            $o_form->addElement('hidden', "displayLimit[$this->listId]");
            $o_form->addElement('hidden', "searchField[$this->listId]");
            $o_form->addElement('hidden', "pageOffset[$this->listId]");
            if ($this->getMatchedRecords() > 0) {
                $s_pagination = sprintf(_('%1$d to %2$d of %3$d'),
                        $this->getRecordOffset() + 1,
                        $this->getRecordOffset() + $s_numListed,
                        $this->getMatchedRecords());
            }
            else {
                $s_pagination = '0 found'; 
            }
        }

        $o_form->addElement('text', "searchString[$this->listId]", null, 
                array('size' => 15, 'style' => 'vertical-align: middle;', 'onfocus' => 'this.select();',
                    'accesskey' => $this->o_output->getAccessKey(_('Search for'))));
        $o_form->addElement('submit', 'query_submit', _('» Search'), 
                array('style' => 'vertical-align: bottom;'));
        if ($this->listAll) {
            $o_form->addElement('submit', 'listall_submit', _('» List All'), 
                array('onclick' => "document.search_box['searchString[$this->listId]'].value = '';", 
                    'style' => 'vertical-align: bottom;'));
        }

        // Add as hidden elements any persistent data
        foreach ($this->persistentData as $s_key => $s_val) {
            $o_form->addElement('hidden', $s_key, $s_val);
        }

        $a_defaults = array();
        foreach ($this->extraSearchElements as $a_element) {
            $o_form->addElement('select', $a_element[1], null, $a_element[2], $a_element[4]);
            $a_defaults[$a_element[1]] = $a_element[3];
        }

        $o_form->setDefaults($a_defaults);
        $o_form->accept($o_renderer);
        if ($s_pagination === false) {
            $s_pagination = $o_renderer->elementToHtml("pageOffset[$this->listId]");
        }

        // }}}
        // {{{ template preparation

        $tmp_help = _('Search for items in the list by entering a search term in the box below.  To search between two dates you can enter the dates in the following format: mm/dd/yyyy - mm/dd/yyyy');
        $s_findText = $this->o_output->highlightAccessKey(_('Search terms')) . ' ' .
            $this->o_output->getHelpLink($tmp_help, _('Search Help'));
        $s_searchSubmit = $o_renderer->elementToHtml('query_submit');  
        if ($this->listAll) {
            $s_searchSubmit .= ' ' . $o_renderer->elementToHtml('listall_submit');
        }

        if ($in_focusSearch) {
            $this->o_output->o_tpl->append('javascript', '<script>addOnload(function () { document.search_box["searchString[' . $this->listId . ']"].focus(); });</script>');
        }

        $o_searchWidget =& new FF_Smarty('searchTable');
        $o_searchWidget->assign(array('T_search_header' => sprintf(_('Search %s'), $in_pluralText), 
                    'is_type_locked' => $this->typeLocked,
                    'T_search_find' => $s_findText,
                    'T_search_terms' => $o_renderer->elementToHtml("searchString[$this->listId]"),
                    'T_search_submit' => $s_searchSubmit));

        if ($a_listVars['searchBoxType'] != SEARCH_BOX_ONLYSEARCH) {
            if ($s_numListed == 0) {
                $s_foundText = _('No Results');
            }
            else {
                $s_foundText = sprintf(_('Results %1$d - %2$d of %3$d results.'), 
                        $this->getRecordOffset() + 1,
                        $this->getRecordOffset() + $s_numListed,
                        $this->getMatchedRecords());
            }

            $o_searchWidget->assign(array('T_search_found' => $s_foundText));
        }

        if ($a_listVars['searchBoxType'] == SEARCH_BOX_SIMPLE) {
            $o_searchWidget->assign(array('T_switch_type' => $this->o_output->link(
                            FastFrame::selfURL(array_merge($this->persistentData, $a_listVars, 
                                    array('searchBoxType' => SEARCH_BOX_ADVANCED))), '&#187; ' . _('More Search Options'))));
        }
        elseif ($a_listVars['searchBoxType'] == SEARCH_BOX_ADVANCED) {
            $s_limitText = sprintf(_('Display %s results per page'), 
                    $o_renderer->elementToHtml("displayLimit[$this->listId]"));

            $o_searchWidget->assign(array('T_switch_type' => $this->o_output->link(
                            FastFrame::selfURL(array_merge($this->persistentData, $a_listVars, 
                                    array('searchBoxType' => SEARCH_BOX_SIMPLE))), '&#171; ' . _('Fewer Search Options')),
                        'T_search_limit' => $s_limitText,
                        'T_search_fields' => _('Search fields'),
                        'T_search_fieldSelect' => $o_renderer->elementToHtml("searchField[$this->listId]"),
                        'T_search_viewing' => sprintf(_('Viewing Page %s'), $s_pagination)));
        }


        if (count($this->extraSearchElements) > 0) {
            $o_searchWidget->assign('has_extra_elements', true);
            foreach ($this->extraSearchElements as $a_element) {
                $o_searchWidget->append('extraElements', 
                        array('T_desc' => $a_element[0], 'T_element' => $o_renderer->elementToHtml($a_element[1])));
            }
        }

        return $o_renderer->toHtml($o_searchWidget->fetch());

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
        $lang_firstPage = array('title' => _('Go to First Page'));
        $lang_nextPage  = array('title' => _('Go to Next Page'));
        $lang_prevPage  = array('title' => _('Go to Previous Page'));
        $lang_lastPage  = array('title' => _('Go to Last Page'));
        $lang_atFirst   = array('title' => _('Disabled'));
        $lang_atLast    = array('title' => _('Disabled'));
      
        $a_urlVars = array_merge($this->persistentData, $this->getAllListVariables());
        // set up the four actions
        $a_navigation = array();
        $a_navigation['first']    = $this->getPageOffset() > 1 ? 
                                    $this->o_output->link(
                                            FastFrame::selfURL(array_merge($a_urlVars, array(
                                                    "pageOffset[$this->listId]" => $this->getPageId('first')))), 
                                            '&#171; ' . _('First'), $lang_firstPage) : '&nbsp;';

        $a_navigation['previous'] = $this->getPageOffset() > 1 ?
                                    $this->o_output->link(
                                            FastFrame::selfURL(array_merge($a_urlVars, array(
                                                    "pageOffset[$this->listId]" => $this->getPageId('previous')))), 
                                            '&#139; ' . _('Previous'), $lang_prevPage) : '&nbsp;';

        $a_navigation['next']     = $this->getPageOffset() < $this->getPageId('last') ? 
                                    $this->o_output->link(
                                            FastFrame::selfURL(array_merge($a_urlVars, array(
                                                    "pageOffset[$this->listId]" => $this->getPageId('next')))), 
                                            _('Next') . ' &#155;', $lang_nextPage) : '&nbsp;';

        $a_navigation['last']     = $this->getPageOffset() < $this->getPageId('last') ? 
                                    $this->o_output->link(
                                            FastFrame::selfURL(array_merge($a_urlVars, array(
                                                    "pageOffset[$this->listId]" => $this->getPageId('last')))), 
                                            _('Last') . ' &#187;', $lang_lastPage) : '&nbsp;';
        
        return $a_navigation;
    }

    // }}}
    // {{{ generateSortFields()

    /**
     * Generate a set of column headers which are HTML'd to have images
     * and links so they can be clicked on to sort the page.
     *
     * @return array An array of the sort fields with the links and
     * descriptions of the cell
     */
    function generateSortFields()
    {
        $a_listVars = array_merge($this->persistentData, $this->getAllListVariables());
        foreach ($this->getColumnData() as $a_colData) {
            // Check to see if it is searchable and we're not in print mode.
            // If so build a link to sort on the column
            if (!empty($a_colData['sort'])) {
                // if we previously sorted on this field, reverse the sort direction
                if ($this->getSortField() == $a_colData['sort']) {
                    $tmp_sort = $this->getSortOrder() ? 0 : 1;
                }
                // this is a new column sort, default sort
                else {
                    $tmp_sort = $this->getSortOrder();
                }

                // Modify the two get variables for this sort
                $a_listVars["sortField[$this->listId]"] = $a_colData['sort'];
                $a_listVars["sortOrder[$this->listId]"] = $tmp_sort;
                $tmp_title = sprintf(_('Sort %1$s (%2$s)'), 
                        (isset($a_colData['title']) ? $a_colData['title'] : $a_colData['name']), 
                        $tmp_sort ? _('Ascending') : _('Descending')); 
                $tmp_href = $this->o_output->link(FastFrame::selfURL($a_listVars), 
                        $a_colData['name'], array('title' => $tmp_title ));

                // see if we should display an arrow 
                if ($this->getSortField() == $a_colData['sort']) {
                    // Determine which image to display
                    $tmp_img = $this->getSortOrder() ? 
                        $this->o_output->imgTag('up.png', 'arrows', array('align' => 'middle', 'height' => 8, 'width' => 8)) : 
                        $this->o_output->imgTag('down.png', 'arrows', array('align' => 'middle', 'height' => 8, 'width' => 8));
                    $tmp_href .= ' ' . $this->o_output->link(FastFrame::selfURL($a_listVars), 
                            $tmp_img, array('title' => $tmp_title)); 
                }
                
                if (isset($a_colData['style'])) {
                    $tmp_href = '<div style="' . $a_colData['style'] . '">' . $tmp_href . '</div>';
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
    // {{{ addSearchSelect()

    /**
     * Adds a select list to the search box.
     *
     * @param string $in_desc The description for the select 
     * @param string $in_name The select name
     * @param array $in_options The array of options
     * @param string $in_default (optional) The default option
     * @param array $in_params (optional) An array of parameters for the
     *        select list
     *
     * @access public
     * @return void
     */
    function addSearchSelect($in_desc, $in_name, $in_options, $in_default = null, $in_params = array('style' => 'vertical-align: middle;', 'onchange' => 'this.form.submit();')) 
    {
        FF_Request::setParam($in_name, FF_Request::getParam($in_name, 'gps', $in_default), 's');
        $this->extraSearchElements[] = array($in_desc, $in_name, $in_options,
                FF_Request::getParam($in_name, 's'), $in_params);
    }

    // }}}
    // {{{ getListId()

    /**
     * Gets the list id
     *
     * @access public
     * @return string The unique id for this list
     */
    function getListId()
    {
        return $this->listId;
    }

    // }}}
    // {{{ getPageId()

    /**
     * Calculates one of the four available page ID's.
     *
     * @param string $in_type What type of page id to get.  Available options are: 'first',
     *                        'previous', 'next', 'last'
     *
     * @access public
     * @return int The page ID
     */
    function getPageId($in_type)
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
     * in, defaulting to SEARCH_BOX_SIMPLE if value is not present.
     *
     * @param int $in_value (optional) Search box type. 
     * @param bool $in_updateSession Update the session parameter with
     *        the new value?
     *
     * @access public
     * @return void
     */
    function setSearchBoxType($in_value = null, $in_updateSession = true)
    {
        if (is_null($in_value)) {
            $this->searchBoxType = FF_Request::getParam('searchBoxType', 'gps', SEARCH_BOX_SIMPLE);
        }
        else {
            $this->searchBoxType = $in_value;
        }

        if ($this->searchBoxType == SEARCH_BOX_ONLYSEARCH) {
            $this->toggleListAll(false);
            $this->toggleTypeLocked(true);
        }

        if ($in_updateSession) {
            FF_Request::setParam('searchBoxType', $this->searchBoxType, 's');
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
        $this->displayLimit = (int) abs(FF_Request::getParam("displayLimit[$this->listId]", 'gps', $in_limit));
        // If it's somehow set to 0 (i.e. not passed into the constructor) then make it a default number
        if (!$this->displayLimit) {
            $this->displayLimit = 20;
        }

        FF_Request::setParam('displayLimit[\'' . $this->listId . '\']', $this->displayLimit, 's');
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
            $this->pageOffset = (int) abs(FF_Request::getParam("pageOffset[$this->listId]", 'gps', 1));
        }
        else {
            $this->pageOffset = (int) $in_value;
        }

        FF_Request::setParam('pageOffset[\'' . $this->listId . '\']', $this->pageOffset, 's');
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
        $this->sortOrder = (int) FF_Request::getParam("sortOrder[$this->listId]", 'gps', $in_sort);
        FF_Request::setParam('sortOrder[\'' . $this->listId . '\']', $this->sortOrder, 's');
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
        $this->sortField = FF_Request::getParam("sortField[$this->listId]", 'gps', $in_field);
        FF_Request::setParam('sortField[\'' . $this->listId . '\']', $this->sortField, 's');
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
     * Sets the search string variable from $in_value or GET/POST/SESSION if not passed in,
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
            $this->searchString = FF_Request::getParam("searchString[$this->listId]", 'gps');
        }
        else {
            $this->searchString = $in_value;
        }

        FF_Request::setParam('searchString[\'' . $this->listId . '\']', $this->searchString, 's');
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
     * Sets the search field variable from $in_value or GET/POST/SESSION
     * if not passed in, defaulting to all fields if not present.
     *
     * @param string $in_value (optional) What field to search on. 
     *
     * @access public
     * @return void
     */
    function setSearchField($in_value = null)
    {
        if (is_null($in_value)) {
            $this->searchField = FF_Request::getParam("searchField[$this->listId]", 'gps', $this->allFieldsKey);
        }
        else {
            $this->searchField = $in_value;
        }

        FF_Request::setParam('searchField[\'' . $this->listId . '\']', $this->searchField, 's');
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
            $this->searchableFields[] = array('name' => _('All Fields'), 'search' => $this->allFieldsKey);
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
                if ($a_val['search'] != $this->allFieldsKey) {
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
     * @param array $in_columnData A multidimensional array with the
     *              primary keys in the order the columns are to be
     *              displayed.
     *              e.g. $colVars[0] = array('name' => 'Col 1', 'sort'
     *              => 'field1', 'title' => 'Title text', 'style' =>
     *              'font-weight: bold;');
     *              Leave out the sort column if the column is not to be
     *              sorted.
     *
     * @access public
     * @return void
     */
    function setColumnData($in_columnData)
    {
        $this->columnData = (array) $in_columnData;
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
    // {{{ setPersistentData()

    /**
     * Sets any persistent data that should be passed from page to page
     * of the list, such as actionId.
     *
     * @param array $in_persistentData (optional) Data in the form of 'name' => 'value'
     *
     * @access public
     * @return void
     */
    function setPersistentData($in_persistentData)
    {
        $this->persistentData = $in_persistentData;
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
        $a_vars["sortField[$this->listId]"] = $this->getSortField();
        $a_vars["sortOrder[$this->listId]"] = $this->getSortOrder();
        $a_vars["sortField[$this->listId]"] = $this->getSortField();
        $a_vars["searchString[$this->listId]"] = $this->getSearchString();
        $a_vars["searchField[$this->listId]"] = $this->getSearchField();
        $a_vars["pageOffset[$this->listId]"] = $this->getPageOffset();
        $a_vars["displayLimit[$this->listId]"] = $this->getDisplayLimit();
        $a_vars['searchBoxType'] = $this->getSearchBoxType();
        return $a_vars;
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
    // {{{ toggleListAll()

    /**
     * Toggle the visibility of the list all button
     *
     * @param bool $in_listAll Show the list all button?
     *
     * @access public
     * @return void
     */
    function toggleListAll($in_listAll)
    {
        return $this->listAll = $in_listAll;
    }

    // }}}
    // {{{ toggleTypeLocked()

    /**
     * Toggle whether or not the list type is locked
     *
     * @param bool $in_locked Whether type is locked or not
     *
     * @access public
     * @return void
     */
    function toggleTypeLocked($in_locked)
    {
        $this->typeLocked = $in_locked;
    }

    // }}}
}
?>
