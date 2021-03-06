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
// | Authors: Jason Rust <jrust@codejanitor.com>                          |
// +----------------------------------------------------------------------+

// }}}
// {{{ requires

require_once dirname(__FILE__) . '/../Action.php';

// }}}
// {{{ class FF_Action_Display

/**
 * The FF_Action_Display:: class handles displaying a representation of the model to the
 * user
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0
 * @access  public
 * @package Action
 */

// }}}
class FF_Action_Display extends FF_Action {
    // {{{ properties

    /**
     * Any persistent data that should be passed on to the search box
     * @var array
     */
    var $persistentData = array('actionId' => ACTION_LIST);

    // }}}
    // {{{ run()

    /**
     * Displays data about the model.
     *
     * @access public
     * @return object The next action object
     */
    function run()
    {
        if (!$this->fillModelWithData()) {
            return $this->o_nextAction;
        }

        // Check perms after filling data so object perms work
        if (!$this->checkPerms()) {
            return $this->o_nextAction;
        }

        $this->renderSearchBox();
        $this->o_output->setPageName($this->getPageName());
        $this->renderDisplay();
        return $this->o_nextAction;
    }

    // }}}
    // {{{ renderDisplay()

    /**
     * Renders the display over the table.
     *
     * @access public
     * @return void
     */
    function renderDisplay()
    {
        // interface
    }

    // }}}
    // {{{ renderSearchBox()

    /**
     * Renders the search box for this module.
     *
     * @param bool $in_focusSearch (optional) Focus the search box?
     *
     * @access public
     * @return void
     */
    function renderSearchBox($in_focusSearch = true)
    {
        if (FF_Request::getParam('isPopup', 'gp', false)) {
            return;
        }

        require_once dirname(__FILE__) . '/../List.php';
        $o_actionHandler =& FF_ActionHandler::singleton();
        $o_list = new FF_List($o_actionHandler->getAppId() . $o_actionHandler->getModuleId() . $this->persistentData['actionId'], null, null, FF_Request::getParam('defDispLimit', 's'));
        $o_list->setSearchBoxType(SEARCH_BOX_ONLYSEARCH, false);
        $o_list->setPersistentData($this->persistentData);
        $this->o_output->o_tpl->assign(array('has_search_box' => true,
                    'W_search_box' => $o_list->renderSearchBox($this->getPluralText(), $in_focusSearch)));
    }

    // }}}
    // {{{ fillModelWithData()

    /**
     * Fills the model object with data for the field being edited.  If an error occurs in
     * getting the data we set the problem action id.
     *
     * @access public
     * @return bool True if the model loaded successfully, false otherwsie
     */
    function fillModelWithData()
    {
        $b_result = $this->o_model->fillById(FF_Request::getParam('objectId', 'gp'));
        if (!$b_result) {
            $this->o_output->setMessage(
                sprintf(_('Could not find the specified %s'), $this->getSingularText()),
                FASTFRAME_ERROR_MESSAGE
            );
            $this->o_nextAction->setNextActionId(ACTION_LIST);
            return false;
        }

        return true;
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
    // {{{ getPluralText()

    /**
     * Gets the text that describes a plural version of the items displayed
     *
     * @access public
     * @return string The plural text
     */
    function getPluralText()
    {
        return _('Items');
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
        return sprintf(_('%s Display'), $this->getSingularText());
    }

    // }}}
}
?>
