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
// {{{ class FF_Menu_DOM

/**
 * The FF_Menu_DOM:: class generates the javascript for the domMenu. 
 *
 * @see http://mojavelinux.com/forum/viewtopic.php?t=141
 * @version Revision: 1.0 
 * @author  Jason Rust <jason@rustyparts.com>
 * @access  public
 * @package Menu 
 */

// }}}
class FF_Menu_DOM extends FF_Menu {
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
        if (!$this->_isMenuCached()) {
            $this->_importMenuVars();
            // Create all the javascript needed for the menu 
            $s_domMenu = '
            <script type="text/javascript" src="' . $this->o_registry->getRootFile('domMenu.js', 'javascript', FASTFRAME_WEBPATH) . '"></script>
            <script type="text/javascript">
            domMenu_settings.set("domMenu_main", new Hash(
                "expandMenuArrowUrl", "' . $this->o_output->imgTag('right-black.gif', 'arrows', array('onlyUrl' => true)) . '",
                "subMenuWidthCorrection", -1,
                "verticalSubMenuOffsetX", -1,
                "verticalSubMenuOffsetY", -1,
                "openMouseoverMenuDelay", -1,
                "openMousedownMenuDelay", 0,
                "closeClickMenuDelay", 0,
                "closeMouseoutMenuDelay", -1
            ));
            ' . $this->_generateMenuVars() . '
            </script>';
            $this->_saveMenuToCache($s_domMenu);
        }

        // Turn on menu
        $this->o_output->touchBlock('switch_menu');
        $s_domMenu = $this->_getCachedMenu();
        // Because we don't know which node will be the last because of perms
        // We have to take off the last comma from the last node of each set
        $s_domMenu = preg_replace('/,(\s+)\)/', '\\1)', $s_domMenu);
        // Load menu js
        $this->o_output->assignBlockData(array('T_javascript' => $s_domMenu), 'javascript');
    }

    // }}}
    // {{{ _generateMenuVars()

    /**
     * Generates the JS menu vars.
     *
     * @see The help docs for domMenu to see the structure of the js hash table.
     * @access private
     * @return string The javascript menu vars
     */
    function _generateMenuVars()
    {
        $s_js = ''; 
        foreach ($this->menuVariables as $a_topLevelData) {
            foreach ($a_topLevelData['vars'] as $a_data) {
                $s_jsNode = $this->_getMenuNode($a_data, 0);
                $s_js .= $this->_replaceAppPlaceholder($a_topLevelData['app'], $s_jsNode);
            }
        }

        // Create javascript, and initialize level counter
        $s_js = '
        <?php $a_count = array(); $a_count[0] = 0; ?>
        domMenu_data.set("domMenu_main", new Hash(
            ' . $s_js . '
        ));';

        return $s_js;
    }

    // }}}
    // {{{ _getMenuNode()

    /**
     * Recursively go through a top level menu array and return the contents for the DOM
     * menu hash structure.
     *
     * @param array $in_data The data to recurse.  Needs to have the structure as set out in
     *                       menu.php
     * @param int $in_level What sub-level of the menu we are at.
     *
     * @see menu.php
     * @access private
     * @return string The javascript for this specific node
     */
    function _getMenuNode($in_data, $in_level)
    {
        $in_data = $this->_formatMenuNodeData($in_data, true);
        $in_data['contents'] = $in_data['icon'] . $in_data['statusText'];
        $tmp_pad = str_repeat(' ', $in_level * 2);

        // Create js for this node
        // The hash number has to be dynamic so perms will work
        $s_jsNode = "
        $tmp_pad<?php echo ++\$a_count[$in_level]; ?>, new Hash(
        $tmp_pad    'contents', '<?php echo addcslashes(_('{$in_data['contents']}'), '\''); ?>',
        $tmp_pad    'uri', '{$in_data['urlParams']}',
        $tmp_pad    'target', '{$in_data['target']}',
        $tmp_pad    'statusText', '<?php echo addcslashes(_('{$in_data['statusText']}'), '\''); ?>'";

        // Recurse sub-elements
        $s_nextLevel = $in_level + 1;
        $s_initCounter = false;
        foreach ($in_data as $s_key => $a_data) {
            if (is_int($s_key) && is_array($a_data)) {
                // See if we need to init counter for this level
                if (!$s_initCounter) {
                    $s_jsNode .= ",
                    $tmp_pad<?php \$a_count[$s_nextLevel] = 0; ?>";
                    $s_initCounter = true;
                }

                $s_jsNode .= $this->_getMenuNode($a_data, $s_nextLevel);
            }
        }

        $s_jsNode .= "
        $tmp_pad),";

        $s_jsNode = $this->_processPerms($in_data, $s_jsNode);
        $s_jsNode = $this->_processApps($in_data, $s_jsNode);
        return $s_jsNode;
    }

    // }}}
}
?>
