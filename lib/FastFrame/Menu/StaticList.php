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
     * The css id we use for the top level <ul>
     * @var string
     */
    var $ulId = 'mainMenu';

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
            $this->o_fileCache->save($s_menu, $this->cacheFile);
        }

        $s_menu = $this->_getCachedMenu();
        // Remove empty lists
        $s_menu = preg_replace('/<ul.*?>\s+<\/ul>/', '', $s_menu);
        // Turn on menu
        if (!FastFrame::isEmpty($s_menu)) {
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
        $s_html = '<ul id="' . $this->ulId . '">'; 
        foreach ($this->menuVariables as $a_topLevelData) {
            foreach ($a_topLevelData['vars'] as $a_data) {
                $s_node = $this->_getMenuNode($a_data, 0);
                $s_html .= $this->_replaceAppPlaceholder($a_topLevelData['app'], $s_node);
            }
        }

        $s_html .= '</ul>';
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
        static $s_lastLevel;
        if (!isset($s_lastLevel)) {
            $s_lastLevel = 0;
        }

        $tmp_nl = $this->debug ? "\n" : ' '; 
        $in_data = $this->_formatMenuNodeData($in_data, true);
        // Create html for this node
        $s_node = '<li>';
        if (!empty($in_data['urlParams'])) {
            $tmp_link = $this->o_output->link($in_data['urlParams'], $in_data['contents'], array('title' => $in_data['statusText'], 'target' => $in_data['target']));
            $tmp_link = preg_replace('/title=".*?"/', 'title="<?php echo _(\'' . $in_data['statusText'] . '\'); ?>"', $tmp_link);
            $tmp_link = preg_replace('/">.*?<\/a>/', '">' . $in_data['icon'] . '<?php echo _(\'' . $in_data['contents'] . '\'); ?></a>', $tmp_link);
            $s_node .= $tmp_link;
        }
        else {
            $s_node .= "<a href=\"#\">{$in_data['icon']}<?php echo _('{$in_data['contents']}'); ?></a>";
        }

        // Recurse sub-elements
        $s_nextLevel = $in_level + 1;
        $b_hitSub = false;
        foreach ($in_data as $s_key => $a_data) {
            if (is_int($s_key) && is_array($a_data)) {
                // Let them know there is a submenu
                if (!$b_hitSub) {
                    $b_hitSub = true;
                    $s_node = preg_replace('|<li>(<a.*?>)(.*?)</a>|', '<li class="parent">\\1<div style="cursor: pointer; float: left;">\\2</div><div style="float: right;">&#187;</div></a>', $s_node);
                }

                $s_node .= $this->_getMenuNode($a_data, $s_nextLevel);
            }
        }

        // If we are coming into a level or out of a level, need to add
        // the <ul> wrappers (where we do it is important so it shows up
        // correctly with varying permissions)
        if ($in_level < $s_lastLevel) {
            $s_node .= "</ul>$tmp_nl";
        }

        $s_node .= "$tmp_nl</li>";
        $s_node = $this->_processPerms($in_data, $s_node);
        $s_node = $this->_processApps($in_data, $s_node);
        if ($in_level > $s_lastLevel) {
            $s_node = "<ul>$tmp_nl" . $s_node;
        }

        $s_lastLevel = $in_level;
        return $s_node;
    }

    // }}}
}
?>
