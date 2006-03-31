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
// {{{ class FF_Output_AJAX

/**
 * The FF_Output_Page:: class provides functions for creating output for
 * AJAX requests.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @access  public
 * @package Output
 */

// }}}
class FF_Output_AJAX extends FF_Output {
    // {{{ properties

    /**
     * The xml that we print out
     * @var string
     */
    var $xml = '';

    /**
     * An array of nodes that are converted to XML
     * @var array
     */
    var $_nodes = array();

    // }}}
    // {{{ display()

    /**
     * Displays the output accumulated thus far.
     *
     * @access public
     * @return void
     */
    function display()
    {
        $this->xml .= $this->arrayToXML($this->_nodes);
        $this->_renderMessages();
        header('Content-Type: text/xml');
        print '<?xml version="1.0" encoding="ISO-8859-1"?>';
        print '<ajax>';
        print $this->xml;
        print '</ajax>';
    }

    // }}}
    // {{{ addNode()

    /**
     * Adds an XML node to the output XML
     *
     * @param string $in_key The element key
     * @param mixed $in_value The element value.  Can be string or array
     *
     * @return void
     */
    function addNode($in_key, $in_value)
    {
        $this->_nodes[$in_key] = $in_value;
    }

    // }}}
    // {{{ arrayToXML()

    /**
     * Converts an array to xml.
     * Initial idea: http://gvtulder.f2o.org/notities/arraytoxml/
     *
     * @param array $in_array The array to convert
     * @param string $in_topNode (optional) The top-level name of the node
     * @param string $in_nodeName (optional) The node name for integer arrays
     * @param int $in_level (optional) The indent level
     */
    function arrayToXML($in_array, $in_topNode = null, $in_nodeName = 'el', $in_level = 1) 
    {
        $s_xml = '';
        if ($in_level == 1 && !is_null($in_topNode)) {
            $s_xml .= "<$in_topNode>";
        }

        foreach ($in_array as $key => $value) {
            if (is_array($value)) {
                // Here we handle integer keys
                // NOTE: We don't handle mixed int/string arrays
                if (isset($value[0])) {
                    $s_xml .= $this->arrayToXML($value, $in_topNode, $key, $in_level);
                }
                else {
                    if (is_int($key)) {
                        $key = $in_nodeName;
                    }

                    $s_xml .= "<$key>";
                    $s_xml .= $this->arrayToXML($value, $key, $in_nodeName, $in_level + 1);
                    $s_xml .= "</$key>";
                }
            }
            else {
                // Remove control characters -- breaks XML
                $value = preg_replace('/[[:cntrl:]]/', '', $value);
                if (trim($value) == '') {
                    $s_xml .= "<$key />";
                }
                else if (htmlspecialchars($value) != $value) {
                    $s_xml .= "<$key><![CDATA[$value]]></$key>";
                } 
                else {
                    $s_xml .= "<$key>$value</$key>";
                }
            } 
        }

        if ($in_level == 1 && !is_null($in_topNode)) {
            $s_xml .= "</$in_topNode>";
        }

        return $s_xml;
    }

    // }}}
    // {{{ setMessage()

    /**
     * Set the message and the mode associated with it
     *
     * @param mixed $in_message The message to inform the user what has just taken place.
     *                          If it is an array then we will register all of them.
     * @param string $in_mode (optional) The mode, which is then translated into an image
     * @param bool $in_top (optional) Put the message at the top of the stack instead of
     *             bottom?
     *
     * @access public
     * @return void
     */
    function setMessage($in_message, $in_mode = FASTFRAME_NORMAL_MESSAGE, $in_top = false)
    {
        parent::setMessage($in_message, $in_mode, $in_top);
        // If it's an error then we assume the operation failed, so send out the error code.
        if ($in_mode == FASTFRAME_ERROR_MESSAGE) {
            header('HTTP/1.0 500 Unsuccessful Action');
        }
    }

    // }}}
    // {{{ _renderMessages()

    /**
     * Renders any messages to XML
     *
     * @access private
     * @return string The XML for the messages
     */
    function _renderMessages()
    {
        $a_messages = array();
        foreach ($this->messages as $a_message) {
            $a_messages[] = array('txt' => $a_message[0], 'type' => $a_message[1]);
        }

        $this->xml .= $this->arrayToXML($a_messages, 'msgs', 'msg');
    }

    // }}}
}
?>
