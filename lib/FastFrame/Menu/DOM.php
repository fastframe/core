<?php
/** $Id: DOM.php,v 1.3 2003/02/08 00:10:56 jrust Exp $ */
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
// {{{ class FastFrame_Menu_DOM

/**
 * The FastFrame_Menu_DOM:: class generates the javascript for the domMenu. 
 *
 * @see http://mojavelinux.com/forum/viewtopic.php?t=141
 * @version Revision: 1.0 
 * @author  Jason Rust <jason@rustyparts.com>
 * @access  public
 * @package Menu 
 */

// }}}
class FastFrame_Menu_DOM extends FastFrame_Menu {
    // {{{ constructor()

    /**
     * Sets up the initial variables on class initialization.
     *
     * @access public
     * @return void
     */
    function FastFrame_Menu_DOM()
    {
        parent::FastFrame_Menu();
    }

    // }}}
    // {{{ renderMenu()

    /**
     * Renders the DOM menu.  Does this by including the domMenu javascript, touching the
     * menu block and generating the menu variables.
     *
     * @access public
     * @return void
     */
    function renderMenu()
    {
        // generate javascript menu vars
        $s_jsMenuVars = $this->_generate_menu_vars();
        // turn on menu
        $this->o_output->touchBlock('switch_menu');
        // load menu js
        $this->o_output->assignBlockData(
            array(
                'T_javascript' => '<script type="text/javascript" src="' . $this->o_registry->getRootFile('domMenu.js', 'javascript', FASTFRAME_WEBPATH) . '"></script>' . "\n" .
                                  '<script type="text/javascript" src="' . $this->o_registry->getRootFile('domMenu_items.js', 'javascript', FASTFRAME_WEBPATH) . '"></script>' . "\n" .
                                  '<script type="text/javascript">' . $s_jsMenuVars . '</script>',
            ),
            'javascript'
        );
    }

    // }}}
    // {{{ _generate_menu_vars()

    /**
     * Generates the JS menu vars.
     *
     * @see The help docs for domMenu to see the structure of the js hash table.
     * @access private
     * @return string The javascript menu vars
     */
    function _generate_menu_vars()
    {
        if (!$this->isMenuVarsImported()) {
            $mxd_return = $this->importMenuVars();
            if (FastFrame_Error::isError($mxd_return)) {
                FastFrame::fatal($mxd_return, __FILE__, __LINE__); 
            }
        }

        // menu is 1 based 
        $s_topLevelCount = 0;
        $tmp_jsArr = array();
        foreach ($this->menuVariables as $s_key => $a_topLevelData) {
            $tmp_js = '';
            foreach ($a_topLevelData['vars'] as $a_data) {
                $tmp_js = $this->_recurse_menu_vars('', $a_data, ++$s_topLevelCount);
                // replace app name
                $tmp_js = str_replace($this->currentAppPlaceholder, $a_topLevelData['app'], $tmp_js);
                $tmp_jsArr[] = $tmp_js;
            }
        }

        // create javascript 
        $s_js = '
        domMenu_data.setItem("domMenu_main", new domMenu_Hash(
            ' . implode(',', $tmp_jsArr) . '
        ));';

        return $s_js;
    }

    // }}}
    // {{{ _recurse_menu_vars()

    /**
     * Recursively go through a top level menu array and return the contents for the DOM
     * menu hash structure.
     *
     * @param string $in_js The javascript string to append.
     * @param array $in_data The data to recurse.  Needs to have the structure as set out in
     *                       menu.php
     * @param int $in_levelCount The count for this level in the menu.
     *
     * @see menu.php
     * @access private
     * @return string The javascript for this specific node
     */
    function _recurse_menu_vars($in_js, $in_data, $in_levelCount) 
    {
        static $s_padNum;
        if (!isset($s_padNum)) {
            $s_padNum = 0;
        }

        // increase padding by one level
        $s_padNum += 4;
        // we assume we are being passed an array that has the contents for this node,
        // then we check if there are any other nodes to recurse into
        $tmp_contents = isset($in_data['contents']) ? addcslashes($in_data['contents'], '\'') : '';
        $tmp_status = isset($in_data['statusText']) ? $in_data['statusText'] : $tmp_contents;
        $tmp_icon = isset($in_data['icon']) ? addcslashes($this->o_output->imgTag($in_data['icon'], 'none'), '\'') . ' ' : '';
        $tmp_contents = $tmp_icon . $tmp_status;
        $tmp_target = isset($in_data['target']) ? $in_data['target'] : '_self';
        $tmp_uri = $this->_get_link_URL($in_data['urlParams']);
        $tmp_pad = str_repeat(' ', $s_padNum);
        $s_js = $in_js . "
        $tmp_pad$in_levelCount, new domMenu_Hash(
        $tmp_pad    'contents', '$tmp_contents',
        $tmp_pad    'uri', '$tmp_uri',
        $tmp_pad    'target', '$tmp_target',
        $tmp_pad    'statusText', '$tmp_status'";

        foreach ($in_data as $s_key => $a_data) {
            if (is_int($s_key) && is_array($a_data)) {
                // menu is 1 based
                $tmp_level = ++$s_key;
                $s_js .= ',';
                $s_js = $this->_recurse_menu_vars($s_js, $a_data, $tmp_level);
            }
        }

        $s_js .= "
        $tmp_pad)";
        $s_padNum -= 4;

        return $s_js;
    }

    // }}}
}
?>
