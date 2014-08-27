<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2009 The Codejanitor Group                        |
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
// | Authors: David Lundgren <dlundgren@syberisle.net>                    |
// +----------------------------------------------------------------------+

// }}}
// {{{ requires

require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/text.php';
require_once 'HTML/QuickForm/date.php';

// }}}
// {{{ class HTML_QuickForm_datecalendar

/**
 * The HTML_QuickForm_datecalendar:: class provides the DYNARCH DHTML calendar
 * for use in webpages. Currently only available with HTML_QuickForm_date.
 *
 * XXX: This should be reimplemented into HTML_QuickForm_calendar so as to include
 *      HTML_QuickForm_date or the various other types as needed. instead of
 *      being HTML_QuickForm_date exclusive (other options should be inputField,
 *      displayArea, or flat)
 * XXX: multiple dates are not available (this is not possible with
 *      HTML_QuickForm_date) [ though it should be possible once the other
 *      types are implemented.
 *
 * @note    DYNARCH DHTML Calendar <http://www.dynarch.com/projects/calendar/>
 * @author  David Lundgren <dlundgren@syberisle.net>
 * @access  public
 */

// }}}
class HTML_QuickForm_datecalendar extends HTML_QuickForm_date
{
    // {{{ constructor



   /**
     * Class constructor
     *
     * @param   string  calendar instance name
     * @param   string  calendar instance label
     * @param   array   Config settings for calendar
     *                  (see "Calendar.setup in details" in "DHTML Calendar
     *                   Widget" online documentation)
     * @param   string  Attributes for the text input form field
     *
     * @access  public
     */
    function HTML_QuickForm_datecalendar($in_name = null, $in_label = null,
                                         $in_config = array(), $in_attr = null)
    {
        $this->HTML_QuickForm_element($in_name, $in_label, $in_attr);
        $this->_persistantFreeze = true;
        $this->_type = 'datecalendar';
        $this->_options = array_merge($this->_options, $in_config);
    }

    // }}}
    // {{{ toHtml()

    /**
     * Renders the input of the form. If id is set for either one of image or
     * button it is ignored, in favor of <name>_calendar.
     *
     * Options:
     *  - image  The image to use as a button.
     *    + src  The image src. This must be specified.
     *  - button The button
     *
     * @return string The javascript for the calendar setup function.
     */
    function toHtml()
    {
        $s_name = $this->getName() . '_calendar';
        if (isset($this->_options['image']) &&
            isset($this->_options['image']['src']))
        {
            // use an image
            require_once 'HTML/QuickForm/image.php';
            $a_attr = $this->_options['image'];
            unset($a_attr['src']);
            $a_attr['id'] = $s_name;
            $a_attr['onclick'] = 'false;';
            $o_img =& HTML_QuickForm_image($s_name,
                                               $this->_options['image']['src'],
                                               $a_attr);
            $s_button = $o_img->toHtml();
        }
        else {
            // use a button
            require_once 'HTML/QuickForm/button.php';
            $a_attr = isset($this->_options['button']) ?
                          $this->_options['button'] : array();
            $a_attr['id'] = $s_name;
            $a_attr['onclick'] = 'false;';
            $o_img =& HTML_QuickForm_button($this->getName() . '_calendar',
                                               '...', $a_attr);
            $s_button = $o_img->toHtml();
        }

        // Load the calendar Javascript files if needed
        $html = $this->_loadCalendarFiles() .
                parent::toHtml() . ' ' . $s_button .
                // try to mitigate any loading problems by loading the javascript
                // after the element
                $this->_renderCalendarSetupJs();

        return $html;
    }

    // }}}
    // {{{ _parseOptionsForJs()

    /**
     * Prepares a javascript representation of the options that the calendar
     * javascript will make use of. Most of these options are taken from
     * DYNARCH's DHTML Calendar.setup function but include customization
     * for use with HTML_QuickForm_date.
     *
     * JavaScript Options (for Calendar):
     *  - onSelect         {String}  The name of a function.
     *  - onClose          {String}  The name of a function.
     *  - showOtherMonths  {Boolean} Display other months, or only the currently
     *                               selected month.
     *  - showTime         {Boolean} Display the time. (enabled automatically if
     *                               h:i is specified, and this is null)
     *  - showWeekNumbers  {Boolean} Display the week numbers.
     *  - use24hr          {Boolean} Use 24 hour time. (enabled automatically if
     *                               H is detected)
     *  - yearStep         {Integer} The amount of years to step forward/backward
     *  - yearRange        {Array}   The years are available
     *  - position         {Array}   The position of the Calendar on the page
     *  - align            {String}  How to align the Calendar in relation to
     *                               the button if not specified
     *  - calculate        {Boolean} Whether or not this will be a calculation
     *                               based calendar
     *  - calcFields       {Array}   The fields to update when doing calculations
     *  - calcStartDate    {Integer} The date to start calculations on.
     *
     * JavaScript Options (for HTML_QuickForm_date):
     *  - format         {String}  Based on PHP's date function. Will automatically
     *                             determine the select* options to be used by
     *                             getSelectedDate, and calendarSelect.
     *
     * @options array $in_options The options to make javascript out of.
     * @return string The javascript representation of the available options
     */
    function _parseOptionsForJs($in_options)
    {
        $a_options = array();

        // is this a calculation based calendar (only does the amount of days
        // between two dates right now
        if (isset($this->_options['calculate'])) {
            $a_options[] = 'calculate: true';

            // the fields to update after calculation
            if (is_array($this->_options['calcFields'])) {
                $a_options[] = 'calcFields: ["' . join('","', $this->_options['calcFields']) . '"]';
            }
            else {
                $a_options[] = 'calcFields: ["' . $this->_options['calcFields'] . '"]';
            }

            // the start date to calculate against
            if (isset($this->_options['calcStartDate'])) {
                if (!is_numeric($this->_options['calcStartDate'])) {
                    $this->_options['calcStartDate'] = strtotime($this->_options['calcStartDate']);
                }
                $a_options[] = 'calcStartDate: ' . $this->_options['calcStartDate'];
            }
        }

        // alignment of the calendar
        if (isset($this->_options['align'])) {
            $a_options[] = 'align: "' . $this->_options['align'] . '"';
        }
        // position of the calendar
        elseif (isset($this->_options['position']) &&
                is_array($this->_options['position']))
        {
            $a_options[] = 'align: [' . $this->_options['position'][0] . ',' .
                                        $this->_options['position'][0] . ']';
        }

        // year step (default is 2
        if (isset($this->_options['yearStep']) && $this->_options['yearStep']) {
            $a_options[] = 'yearStep: ' . (int)$this->_options['yearStep'];
        }

        // other functions to call beyond the default FastFrame ones
        if (isset($this->_options['onSelect']) && $this->_options['onSelect']) {
            $a_options[] = 'onSelect: "' . $this->_options['onSelect'] . '"';
        }
        if (isset($this->_options['onClose']) && $this->_options['onClose']) {
            $a_options[] = 'onClose: "' . $this->_options['onClose'] . '"';
        }

        // display other months (default is true)
        if (isset($this->_options['showOtherMonths']) && !$this->_options['showOtherMonths']) {
            $a_options[] = 'showOtherMonths: false';
        }

        // display the week numbers (default is false)
        if (isset($this->_options['showWeekNumbers']) && $this->_options['showWeekNumbers']) {
            $a_options[] = 'showWeekNumbers: true';
        }

        // is there an option for an date to be empty
        $s_tmp =  'false';
        if (isset($this->_options['addEmptyOption']) && $this->_options['addEmptyOption']) {
            $s_tmp = 'true';
        }
        $a_options[] = "hasEmptyOption: $s_tmp";

        // determine the settings for the javascript to set the date for
        // days: Dld
        $s_format = $this->_options['format'];
        if (false !== ($s_tmp = stripos($s_format, 'd')) ||
            false !== ($s_tmp = strpos($s_format, 'l')))
        {
            $a_options[] = 'selectDay: "' . substr($s_format, $s_tmp, 1) . '"';
        }

        // months: MFm
        if (false !== ($s_tmp = stripos($s_format, 'm')) ||
            false !== ($s_tmp = strpos($s_format, 'F')))
        {
            $a_options[] = 'selectMonth: "' . substr($s_format, $s_tmp, 1) . '"';
        }
        // years: Yy
        if (false !== ($s_tmp = stripos($s_format, 'y'))) {
            $a_options[] = 'selectYear: "' . substr($s_format, $s_tmp, 1) . '"';
        }

        // set the time parameters
        if ((isset($this->_options['showTime']) && $this->_options['showTime']) ||
            (!isset($this->_options['showTime']) &&  // showTime is null, and
              false !== stripos($s_format, 'h') &&   // (H|h):i are detected
              false !== strpos($s_format, 'i')))
        {
            // display the calendars time selector (default is false)
            $a_options[] = "showTime: true";

            // 24 hour time format (default is false, unless H is detected)
            if ((isset($this->_options['use24hr']) && $this->_options['use24hr']) ||
                false !== strpos($this->_options['format'], 'H'))
            {
                $a_options[] = 'time24: true';
            }

            // hour: hH
            if (false !== ($s_tmp = stripos($s_format, 'h'))) {
                $a_options[] = 'selectHour: "' . substr($s_format, $s_tmp, 1) . '"';
            }

            // minutes: i
            if (false !== strpos($s_format, 'i')) {
                $a_options[] = 'selectMinute: "i"';
            }

            // seconds: s
            if (false !== strpos($s_format, 's')) {
                $a_options[] = 'selectYear: "s"';
            }

            // ampm: Aa
            if (false !== ($s_tmp = stripos($s_format, 'a'))) {
                $a_options[] = 'selectAmPm: "' . substr($s_format, $s_tmp, 1) . '"';
            }
        }

        // set the minimum/maximum years
        if (isset($this->_options['minYear']) || isset($this->_options['maxYear'])) {
            $s_minYear = isset($this->_options['minYear']) ?
                            (int)$this->_options['minYear'] : 0;
            $s_maxYear = isset($this->_options['maxYear']) ?
                            (int)$this->_options['maxYear'] : 0;

            $a_options[] = "yearRange: [ $s_minYear, $s_maxYear ]";
        }
        return $a_options;
    }

    // }}}
    // {{{ _renderCalendarSetupJs()

    /**
     * Renders the calendar setup javascript.
     *
     * Options:
     *  - usePrototypeJs : boolean : Whether or not PrototypeJs is available.
     *
     * @return string The javascript for the calendar setup function.
     */
    function _renderCalendarSetupJs()
    {
        $s_name = $this->getName();
        $s_functionId = $s_name . '_calendarInit';

        // use prototypes event to load this after everything has loaded
        $s_onLoad = "$s_functionId();";
        if ($this->_options['usePrototypeJs']) {
            $s_onLoad = "Event.observe(window, 'load', $s_functionId, false);";
        }

        // load the options for this calendar object
        $a_options = $this->_parseOptionsForJs($this->_options);
        $s_options = "var options = {field: '$s_name', " .
                      implode(',', $a_options) . '};';

        $js = "
        <script type=\"text/javascript\">
        <!--
        // {{{ calendarInit()
        function $s_functionId()
        {
            $s_options
            Calendar.setup(options);
        }
        $s_onLoad
        // }}}
        //-->
        </script>
        ";
        return $js;
    }

    // }}}
    // {{{ _loadCalendarFiles()

    /**
     * Renders the html needed to load the Calendar's javascript and css files.
     * This will only render once to prevent multiple trips to the server. It
     * will has an option 'no_files' to allow the application to load these files
     * on it's own, instead of through this interface.
     *
     * Options:
     *  - no_files : boolean : Load the javascript/css files?
     *  - js_setup : string  : The name of the calendar-setup.js file
     *  - js_file  : string  : The name of the calendar.js file
     *  - stripped : boolean : Use the stripped versions of the DHTML Calendar
     *                         files
     *  - language : string  : The language of this object.
     *  - basePath : string  : The web path to get to the calendar javascript files.
     *  - theme    : string  : The path to the calendars theme
     *
     * @return string The html for loading the calendar javascript/theme.
     */
    function _loadCalendarFiles()
    {
        if (isset($this->_options['noFiles']) ||
            defined('HTML_QUICKFORM_DATECALENDAR_LOADED'))
        {
            return '';
        }

        $s_stripped = isset($this->_options['stripped']) ? true : false;

        // calendar-setup.js
        if (isset($this->_options['jsSetup'])) {
            $s_setupFile = $this->_options['jsSetup'];
        }
        elseif ($s_stripped) {
            $s_setupFile = 'calendar-setup_stripped.js';
        }
        else {
            $s_setupFile = 'calendar-setup.js';
        }

        // calendar.js
        if (isset($this->_options['jsFile'])) {
            $s_file = $this->_options['jsFile'];
        }
        elseif ($s_stripped) {
            $s_file = 'calendar_stripped.js';
        } else {
            $s_file = 'calendar.js';
        }

        // locale
        $s_langFile = 'lang/calendar-' . $this->_options['language'] . '.js';

        $s_path = isset($this->_options['basePath']) ? $this->_options['basePath'] : '/';
        $html = "
        <script type=\"text/javascript\" src=\"$s_path/$s_file\"></script>
        <script type=\"text/javascript\" src=\"$s_path/$s_setupFile\"></script>
        <script type=\"text/javascript\" src=\"$s_path/$s_langFile\"></script>";

        if (isset($this->_options['theme'])) {
            $s_theme = $this->_options['theme'];
            $html .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$s_theme\" />";
        }

        define('HTML_QUICKFORM_DATECALENDAR_LOADED', true);
        return $html;
    }
    // }}}
}

// {{{ element registration
HTML_QuickForm::registerElementType('datecalendar',
                                    'lib/FastFrame/Output/QuickForm_Calendar.php',
                                    'HTML_QuickForm_datecalendar');
// }}}
?>
