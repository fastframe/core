<?php
/** $Id: Delete.php,v 1.5 2003/03/13 18:37:35 jrust Exp $ */
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
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function FF_Action_Delete()
    {
        FF_Action::FF_Action();
    }

    // }}}
    // {{{ run()
    
    /**
     * Deletes the object from the database 
     *
     * @access public
     * @return object The next action object
     */
    function run()
    {
        $o_result =& $this->o_model->remove(FastFrame::getCGIParam('objectId', 'gp'));
        if ($o_result->isSuccess()) {
            $this->o_output->setMessage($this->getSuccessMessage());
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
    // {{{ getSuccessMessage()

    /**
     * Gets the message for a successful delete 
     *
     * @access public
     * @return string The message
     */
    function getSuccessMessage()
    {
        return _('Deleted the data successfully.');
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
        return _('There was an error when trying to delete the data.');
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
        $this->o_nextAction->setNextActionId(ACTION_LIST);
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
}
?>
