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

require_once dirname(__FILE__) . '/../Action.php';
require_once dirname(__FILE__) . '/../Output/Table.php';

// }}}
// {{{ class FF_Action_Tree 

/**
 * The FF_Action_Tree:: class handles displaying a tree structure to the user by interfacing
 * with a database schema that supports a tree structure (usually DB_NestedSet)
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package Action 
 */

// }}}
class FF_Action_Tree extends FF_Action {
    // {{{ properties

    /**
     * Array of opened tree folders that are opened
     * @var array
     */
    var $openFolders = array();

    /**
     * The field in the data array that has the primary id
     * @var string
     */
    var $idField;

    /**
     * The field in the data array that has the left_id
     * @var string
     */
    var $leftField;

    /**
     * The field in the data array that has the right_id 
     * @var string
     */
    var $rightField;

    /**
     * The number of root nodes
     * @var int
     */
    var $numRootNodes;

    /**
     * Tells if the current node should be opened or not
     * @var int
     */
    var $isOpen = 0;

    /**
     * Template block that the container is rendered into
     * @var string
     */
    var $templateBlock = 'content_middle';

    /**
     * Template variable name that the container is rendered into
     * @var string
     */
    var $templateVar = 'W_content_middle';

    // }}}
    // {{{ run()
    
    /**
     * Registers the problem message with the output class.
     *
     * @access public
     * @return void
     */
    function run()
    {
        if (!$this->checkPerms()) {
            return $this->o_nextAction;
        }

        $this->o_output->setPageName($this->getPageName());
        $this->renderAdditionalLinks();
        $this->openFolders = FastFrame::getCGIParam($this->getSessionKey(), 's', array());
        $this->doFolderAction();
        $a_nodes = $this->getRootNodes();
        $this->numRootNodes = count($a_nodes);
        if ($this->numRootNodes == 0) {
            $this->renderContainer($this->getEmptySetText());
        }
        else {
            $this->renderContainer($this->renderNodes($a_nodes));
        }

        return $this->o_nextAction;
    }

    // }}}
    // {{{ renderAdditionalLinks()

    /**
     * Renders additional links on the page
     *
     * @access public
     * @return void
     */
    function renderAdditionalLinks()
    {
        // interface
    }

    // }}}
    // {{{ renderContainer()

    /**
     * Renders the container that the tree is contained in
     *
     * @param string $in_tree The html for the tree
     *
     * @access public
     * @return void
     */
    function renderContainer($in_tree)
    {
        $o_table =& new FF_Output_Table();
        $o_table->setTableHeaderText($this->getPageName());
        $o_table->setNumColumns(1);
        $o_table->beginTable();
        $o_tableWidget =& $o_table->getWidgetObject();
        $o_tableWidget->touchBlock('table_row');
        $o_tableWidget->cycleBlock('table_content_cell');
        $o_tableWidget->assignBlockData(array('T_table_content_cell' => $in_tree), 'table_content_cell');
        $this->o_output->assignBlockData(array($this->templateVar => $o_tableWidget->render()), $this->templateBlock);
    }

    // }}}
    // {{{ renderNodes()

    /**
     * A recursive function to render the nodes in a certain level and if any of those
     * folders are open it renders the link below.
     *
     * @param array $in_data The array of data to render
     * @param int $in_level (optional) What level we are at in terms of sub-nodes
     * @param array $in_nodeCounts (optional) An array of how many nodes are in each level.
     *              Used by the recursion to determine the type of line to draw for
     *              representing the tree in previous nodes
     * @param string $in_html The html for the tree
     *
     * @access public
     * @return string The html for the tree 
     */
    function renderNodes($in_data, $in_level = 0, $in_nodeCounts = array(), $in_html = '')
    {
        static $b_lastRoot;
        if (!isset($b_lastRoot)) {
            $b_lastRoot = false;
        }

        if (!is_array($in_data)) {
            return $in_data;
        }

        $s_count = 0;
        $in_nodeCounts[$in_level] = count($in_data);
        foreach ($in_data as $a_node) {
            $this->o_model->reset();
            $this->o_model->importFromArray($a_node);
            $s_count++;
            // need to know when we've hit the last root node for presentation reasons
            if ($in_level == 0 && $s_count == $in_nodeCounts[$in_level]) {
                $b_lastRoot = true;
            }

            $b_isOpen = isset($this->openFolders[$this->o_model->getId()]);
            $b_hasChildren = $this->doesNodeHaveChildren();
            $in_html .= $this->renderNode($in_level, $b_hasChildren, $b_isOpen, $s_count, $in_nodeCounts, $b_lastRoot);
            if ($b_isOpen && $b_hasChildren) {
                $in_html = $this->renderNodes(
                    $this->getChildren(),
                    $in_level + 1, 
                    $in_nodeCounts,
                    $in_html
                );
            }
        }

        return $in_html;
    }

    // }}}
    // {{{ renderNode()

    /**
     * Renders an individual node
     *
     * @param int $in_level The level the folder is at
     * @param bool $in_hasChildren Does this folder have children?
     * @param bool $in_isOpen Is this folder open?
     * @param string $in_count What number we are at in the current level 
     * @param array $in_nodeCounts The total number of nodes for each level 
     * @param bool $in_lastRoot Is this the last root node?
     *
     * @access public 
     * @return void
     */
    function renderNode($in_level, $in_hasChildren, $in_isOpen, $in_count, $in_nodeCounts, $in_lastRoot)
    {
        $s_html = '<div style="white-space: nowrap;">';
        // add branches to the level we are at
        for ($i = 0; $i < $in_level; $i++) {
            // only show line if the current level has a child and if it is not the last root node
            if ($in_nodeCounts[$i] > 1 && !($i == 0 && $in_lastRoot)) {
                $s_html .= $this->getIcon('line');
            }
            else {
                $s_html .= $this->getIcon('empty');
            }
        }
        
        // determine position in tree
        if ($in_level == 0 && $in_count == 1) {
            $s_position = $in_nodeCounts[$in_level] > 1 ? 'top' : 'single';
        }
        elseif ($in_count == $in_nodeCounts[$in_level]) {
            $s_position = 'bottom';
        }
        else {
            $s_position = '';
        }

        // add current branch
        $s_branch = $in_hasChildren ? ($in_isOpen ? 'minus' : 'plus') : 'branch';
        $s_branch .= $s_position; 
        $s_branch = $this->getIcon($s_branch);
        $s_folder = $this->getNodeIcon($in_hasChildren, $in_isOpen) . ' ';
        if ($in_hasChildren) {
            $this->isOpen = $in_isOpen ? 0 : 1;
            $s_branch = $this->o_output->link(FastFrame::selfURL($this->getBranchUrlParams()), $s_branch);
        }

        $s_html .= $s_branch;
        $s_html .= $s_folder; 
        $s_html .= $this->getNodeData();
        $s_html .= '</div>' . "\n";
        return $s_html;
    }

    // }}}
    // {{{ getBranchUrlParams()

    /**
     * Gets the parameters for the branch (open/close) url.
     *
     * @access public
     * @return array The array of url params
     */
    function getBranchUrlParams()
    {
        return array('nodeId' => $this->o_model->getId(), 'expand' => $this->isOpen, 
                'actionId' => $this->currentActionId);
    }

    // }}}
    // {{{ getNodeIcon()

    /**
     * Gets the icon for the node
     *
     * @param bool $in_hasChildren Does this node have children?
     * @param bool $in_isOpen Is this node open?
     *
     * @return string The url for the icon
     */
    function getNodeIcon($in_hasChildren, $in_isOpen)
    {
        return ($in_hasChildren && $in_isOpen) ? $this->getIcon('folder-expanded') : $this->getIcon('folder');
    }

    // }}}
    // {{{ getNodeData()

    /**
     * Gets the data for a particular node, such as the name, or a link, etc.
     *
     * @access public
     * @return string The data from the node that should go next to the folder
     */
    function getNodeData()
    {
        return $this->o_model->getId();
    }

    // }}}
    // {{{ doesNodeHaveChildren()

    /**
     * Checks to see if the node has children
     *
     * @access public
     * @return bool True if it has children, false otherwise
     */
    function doesNodeHaveChildren()
    {
        return ($this->o_model->getLeftId() != ($this->o_model->getRightId() - 1)) ? true : false;
    }

    // }}}
    // {{{ checkPerms()

    /**
     * Check the permissions on this action.
     *
     * @access public
     * @return bool True if everything is ok, false if a new action has been set
     */
    function checkPerms()
    {
        return true;
    }

    // }}}
    // {{{ doFolderAction()

    /**
     * Checks to see if a folder action was performed and if so performs the necessary
     * operations on the open folders array
     *
     * @access public
     * @return void
     */
    function doFolderAction()
    {
        if (($s_id = FastFrame::getCGIParam('nodeId', 'g', false)) !== false) {
            if (FastFrame::getCGIParam('expand', 'g', false)) {
                $this->openFolders[$s_id] = true;
            }
            else {
                unset($this->openFolders[$s_id]);
            }
        }

        $_SESSION[$this->getSessionKey()] = $this->openFolders;
    }

    // }}}
    // {{{ getRootNodes()
    
    /**
     * Returns an array of the root nodes
     *
     * @access public
     * @return array The array of root nodes
     */
    function getRootNodes()
    {
        return $this->o_model->getRootNodes();
    }

    // }}}
    // {{{ getChildren()

    /**
     * Gets the children of the currently loaded id. 
     *
     * @access public
     * @return array The array of children
     */
    function getChildren()
    {
        return $this->o_model->getChildren();
    }

    // }}}
    // {{{ getIcon()

    /**
     * Gets an icon for the tree
     *
     * @param string $in_icon The icon to fetch
     *
     * @access public
     * @return string The img tag for the icon image
     */
    function getIcon($in_icon)
    {
        static $a_imagePaths;
        if (!isset($a_imagePaths)) {
            $a_imagePaths = array();
        }

        if (!isset($a_imagePaths[$in_icon])) {
            $a_imagePaths[$in_icon] = $this->o_output->imgTag($in_icon . '.gif', 'tree');
        }

        return $a_imagePaths[$in_icon];
    }

    // }}}
    // {{{ getSingularText()

    /**
     * Gets the text that describes a singular version of the items displayed
     *
     * @access public
     * @return string The singular text
     */
    function getSingularText()
    {
        return _('Item');
    }

    // }}}
    // {{{ getPageName()

    /**
     * Returns the page name for this action 
     *
     * @access public
     * @return string The page name
     */
    function getPageName()
    {
        return sprintf(_('%s Tree'), $this->getSingularText());
    }

    // }}}
    // {{{ getSessionKey()

    /**
     * Gets the session key for this module (if unique makes it possible to have several
     * trees in session
     *
     * @access public
     * @return string
     */
    function getSessionKey()
    {
        return 'itemTree';
    }

    // }}}
    // {{{ getEmptySetText()

    /**
     * Returns the text for when there is an empty data set
     *
     * @access public
     * @return string
     */
    function getEmptySetText()
    {
        $s_text = '<div style="text-align: center; font-style: italic;">';
        $s_text .= sprintf(_('A %s has not yet been created.'), strtolower($this->getSingularText()));
        $s_text .= '</div>';
        return $s_text;
    }
    
    // }}}
}
?>
