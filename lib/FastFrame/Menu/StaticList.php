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
// {{{ class FF_Menu_StaticList

/**
 * The FF_Menu_StaticList:: class generates the cached html for a static html menu of
 * hierarchichal menu items.
 *
 * @version Revision: 1.0 
 * @author  Jason Rust <jason@rustyparts.com>
 * @access  public
 * @package Menu 
 */

// }}}
class FF_Menu_StaticList extends FF_Menu {
    // {{{ properties

    /**
     * The css class we use for the each node
     * @var string
     */
    var $cssClass = 'listElement';

    /**
     * The template variable to which we assign the menu
     * @var string
     */
    var $tmplVar = 'content_left';

    // }}}
    // {{{ renderMenu()

    /**
     * Renders the StaticList menu.
     *
     * @access public
     * @return void
     */
    function renderMenu()
    {
        if (!$this->_isMenuCached()) {
            $this->_importMenuVars();
            $s_menu = $this->_generateStaticMenu(); 
            $this->_saveMenuToCache($s_menu);
        }

        // Turn on menu
        $s_menu = $this->_getCachedMenu();
        if (!FastFrame::isEmpty($s_menu, false)) {
            $this->o_output->o_tpl->append($this->tmplVar, $s_menu);
        }
    }

    // }}}
    // {{{ _generateStaticMenu()

    /**
     * Generates the HTML for the static menu. 
     *
     * @access private
     * @return string The HTML for the menu.
     */
    function _generateStaticMenu()
    {
        $s_html = ''; 
        foreach ($this->menuVariables as $a_topLevelData) {
            foreach ($a_topLevelData['vars'] as $a_data) {
                $s_node = $this->_getMenuNode($a_data, 0);
                $s_html .= $this->_replaceAppPlaceholder($a_topLevelData['app'], $s_node);
            }
        }

        return $s_html;
    }

    // }}}
    // {{{ _getMenuNode()

    /**
     * Recursively go through a top level menu array and return the contents for the StaticList
     * menu.
     *
     * @param array $in_data The data to recurse.  Needs to have the structure as set out in
     *                       menu.php
     * @param int $in_level What sub-level of the menu we are at.
     *
     * @see menu.php
     * @access private
     * @return string The html for this specific node 
     */
    function _getMenuNode($in_data, $in_level)
    {
        $in_data = $this->_formatMenuNodeData($in_data, false);

        // Create html for this node
        $tmp_style = empty($in_data['urlParams']) ? 'font-weight: bold;' : '';
        $s_padNum = $in_level * 15;
        $s_node = "<div class=\"$this->cssClass\" style=\"margin-left: {$s_padNum}px; $tmp_style\">";
        $s_node .= $in_data['icon'];
        if (!empty($in_data['urlParams'])) {
            $s_node .= "<?php echo \$o_output->link('{$in_data['urlParams']}', _('{$in_data['contents']}'), array('title' => _('{$in_data['statusText']}'), 'target' => '{$in_data['target']}')); ?>";
        }
        else {
            $s_node .= "<?php echo _('{$in_data['contents']}'); ?>";
        }

        $s_node .= "</div>\n";

        // Recurse sub-elements
        $s_nextLevel = $in_level + 1;
        foreach ($in_data as $s_key => $a_data) {
            if (is_int($s_key) && is_array($a_data)) {
                $s_node .= $this->_getMenuNode($a_data, $s_nextLevel);
            }
        }

        $s_node = $this->_processPerms($in_data, $s_node);
        $s_node = $this->_processApps($in_data, $s_node);
        return $s_node;
    }

    // }}}
}
?>
