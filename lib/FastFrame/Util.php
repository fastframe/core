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
    // {{{ int     bytesToHuman()

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
    // {{{ string  getRandomString()

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
    // {{{ array   createNumericOptionList()

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
}
?>
