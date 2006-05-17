<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2005 The Codejanitor Group                        |
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
// | Authors: The Horde Team <http://www.horde.org>                       |
// |          Jason Rust <jrust@codejanitor.com>                          |
// |          Dan Allen <dan@mojavelinux.com>                             |
// +----------------------------------------------------------------------+

// }}}
// {{{ class FF_Util

/**
 * The FF_Util class provides handy methods.
 *
 * @version Revision: 1.0 
 * @author  Jason Rust <jrust@codejanitor.com>
 * @access  public
 * @package FastFrame
 */

// }}}
class FF_Util {
    // {{{ bytesToHuman()

    /**
     * Converts a number of bytes into a human readable
     * string of kb, mb, or gb depending on what is appropriate
     *
     * @param double $in_bytes The value to format
     * @param integer $in_dec The number of decimals to retain
     * @param integer $in_sens The sensitiveness.  The bigger the number
     *                          the larger the step to the next unit
     *
     * @access public
     * @return string Formatted number string
     */
    function bytesToHuman($in_bytes, $in_dec = 0, $in_sens = 3)
    {
        // the different formats
        $byteUnits[0] = _('Bytes');
        $byteUnits[1] = _('KB');
        $byteUnits[2] = _('MB');
        $byteUnits[3] = _('GB');

        $li           = pow(10, $in_sens);
        $dh           = pow(10, $in_dec);
        $unit         = $byteUnits[0];

        if ($in_bytes >= $li*1000000) {
            $value = round($in_bytes/(1073741824/$dh))/$dh;
            $unit  = $byteUnits[3];
        }
        else if ($in_bytes >= $li*1000) {
            $value = round($in_bytes/(1048576/$dh))/$dh;
            $unit  = $byteUnits[2];
        }
        else if ($in_bytes >= $li) {
            $value = round($in_bytes/(1024/$dh))/$dh;
            $unit  = $byteUnits[1];
        }
        else {
            $value = $in_bytes;
            $in_dec = 0;
        }

        $returnValue = number_format($value, $in_dec);

        return $returnValue.' '.$unit;
    }

    // }}}
    // {{{ getRandomString()

    /**
     * Generates a random string
     *
     * @param int $in_length The length of the string 
     * @param string $in_possibleChars (optional) Specify characters to use
     *
     * @access public
     * @return string The random string
     */
    function getRandomString($in_length, $in_possibleChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
    {
        $possibleCharsLength = strlen($in_possibleChars);
        $string = '';
        while(strlen($string) < $in_length) {
            $string .= substr($in_possibleChars, (mt_rand() % $possibleCharsLength), 1);
        }
        return $string;
    }
    
    // }}}
    // {{{ createNumericOptionList()

    /**
     * Creates a numeric array based on a start and end value
     *
     * @param int $in_start The start number
     * @param int $in_end The end number
     * @param int $in_step The step amount
     * @param string $in_sprintf (optional) The sprintf statement
     *
     * @access public
     * @return array A sequential array of numbers
     */
    function createNumericOptionList($in_start, $in_end, $in_step = 1, $in_sprintf = '%02d')
    {
        $select = array();
        for ($i = $in_start; $i <= $in_end; $i = $i + $in_step) {
            $select[$i] = sprintf($in_sprintf, $i);
        }

        return $select;
    }

    // }}}
    // {{{ createMonthOptionList()

    /**
     * Creates an of months based on the current locale setting
     *
     * @access public
     * @return array An array of 'monthName' => 'monthName'
     */
    function createMonthOptionList()
    {
        $select = array();
        for ($i = 1; $i <= 12; $i++) {
            $time = mktime(0, 0, 0, $i, 1, 2005);
            $select[$i] = date('F', $time);
        }

        return $select;
    }

    // }}}
    // {{{ formatRelativeDate()

    /**
     * Formats a timestamp to a relative date (time elapsed from now),
     * but we also enclose the date in a span so that if they hover over
     * it the actual date comes up.
     *
     * @param int $in_timestamp The timestamp
     * @param bool $in_showShortDate (optional) Show a short date next
     *        to the relative date (i.e. Apr 19 (1 day ago))?  Otherwise
     *        we just show the relative date.
     *
     * @return string The formatted date
     */
    function formatRelativeDate($in_timestamp, $in_showShortDate = true)
    {
        if (time() > $in_timestamp) {
            $s_diff = time() - $in_timestamp;
            $s_lang = _('ago');
        }
        else {
            $s_diff = $in_timestamp - time();
            $s_lang = _('from now');
        }

        if ($s_diff < 10) {
            $s_date = date('g:ia', $in_timestamp);
            $s_reldate = _('right now');
        }
        elseif ($s_diff < 60) {
            $s_date = date('g:ia', $in_timestamp);
            $s_reldate = sprintf(_('%s secs %s'), $s_diff, $s_lang);
        }
        elseif ($s_diff < 3600) {
            $s_date = date('g:ia', $in_timestamp);
            $s_num = round($s_diff / 60);
            $s_reldate = $s_num == 1 ? sprintf(_('%s min %s'), $s_num, $s_lang) :
                sprintf(_('%s mins %s'), $s_num, $s_lang);
        }
        elseif ($s_diff < 86400) {
            $s_date = date('g:ia', $in_timestamp);
            // Round to half
            $s_num = round(($s_diff / 3600) * 2) / 2;
            $s_reldate = $s_num == 1 ? sprintf(_('%s hr %s'), $s_num, $s_lang) :
                sprintf(_('%s hrs %s'), str_replace('.5', '&#189;', $s_num), $s_lang);
        }
        elseif ($s_diff < 2419200) {
            $s_date = date('M jS', $in_timestamp);
            $s_num = round(($s_diff / 86400) * 2) / 2;
            $s_reldate = $s_num == 1 ? sprintf(_('%s day %s'), $s_num, $s_lang) :
                sprintf(_('%s days %s'), str_replace('.5', '&#189;', $s_num), $s_lang);
        }
        elseif ($s_diff < 31536000) {
            $s_date = date('M jS', $in_timestamp);
            $s_num = round(($s_diff / 2419200) * 2) / 2;
            $s_reldate = $s_num == 1 ? sprintf(_('%s month %s'), $s_num, $s_lang) :
                sprintf(_('%s months %s'), str_replace('.5', '&#189;', $s_num), $s_lang);
        }
        else {
            $s_date = date('m/d/y h:ia', $in_timestamp);
            $s_reldate = null;
        }

        if (!empty($s_reldate)) {
            $s_date = $in_showShortDate ? "$s_date ($s_reldate)" : $s_reldate;
            return '<span title="' . date('m/d/y h:ia', $in_timestamp) . '">' . $s_date . '</span>';
        }
        else {
            return $s_date;
        }
    }

    // }}}
    // {{{ truncateString()

    /**
     * Truncates a string to the specified length and adds ... if it is longer than length
     * 
     * @param string $in_str The string
     * @param int $in_len The length
     *
     * @access public
     * @return string The truncated string
     */
    function truncateString($in_str, $in_len)
    {
        return strlen($in_str) > $in_len ? substr($in_str, 0, $in_len) . '...' : $in_str;
    }

    // }}}
}
?>
