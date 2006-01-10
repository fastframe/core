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
// | Authors: Jason Rust <jrust@codejanitor.com>                          |
// +----------------------------------------------------------------------+

// }}}
// {{{ requires

require_once dirname(__FILE__) . '/../Action.php';

// }}}
// {{{ class FF_Action_Delete

/**
 * The FF_Action_Delete:: class deletes the specified object 
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package ActionHandler 
 */

// }}}
class FF_Action_Delete extends FF_Action {
    // {{{ run()
    
    /**
     * Deletes the object from the database 
     *
     * @access public
     * @return object The next action object
     */
    function run()
    {
        if (!$this->checkPerms()) {
            return $this->o_nextAction;
        }

        $o_result =& $this->remove();
        if ($o_result->isSuccess()) {
            $this->o_output->setMessage($this->getSuccessMessage(), FASTFRAME_SUCCESS_MESSAGE);
            $this->setSuccessActionId();
        }
        else {
            $o_result->addMessage($this->getProblemMessage());
            $this->o_output->setMessage($o_result->getMessages(), FASTFRAME_ERROR_MESSAGE);
            $this->setProblemActionId();
        }
        
        return $this->o_nextAction;
    }

    // }}}
    // {{{ remove()

    /**
     * Runs the remove command on the model
     *
     * @access public
     * @return object The Result object
     */
    function remove()
    {
        return $this->o_model->remove(FF_Request::getParam('objectId', 'gp'));
    }

    // }}}
    // {{{ getSuccessMessage()

    /**
     * Gets the message for a successful delete 
     *
     * @access public
     * @return string The message
     */
    function getSuccessMessage()
    {
        return sprintf(_('Deleted the %s successfully.'), $this->getSingularText());
    }

    // }}}
    // {{{ getProblemMessage()

    /**
     * Gets the message for when deleting the data fails 
     *
     * @access public
     * @return string The message
     */
    function getProblemMessage()
    {
        return sprintf(_('There was an error when trying to delete the %s.'), $this->getSingularText());
    }

    // }}}
    // {{{ setProblemActionId()

    /**
     * Sets the actionId to invoke when an error occurs.
     *
     * @access public
     * @return void
     */
    function setProblemActionId()
    {
        // normally return to same page
        $this->setSuccessActionId();
    }

    // }}}
    // {{{ setSuccessActionId()

    /**
     * Sets the actionId to invoke when a successful delete occurs. 
     *
     * @access public
     * @return void
     */
    function setSuccessActionId()
    {
        $this->o_nextAction->setNextActionId(ACTION_LIST);
    }

    // }}}
    // {{{ getSingularText()

    /**
     * Gets the text that describes a singular version of the item deleted 
     *
     * @access public
     * @return string The singular text
     */
    function getSingularText()
    {
        return _('data');
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
}
?>
