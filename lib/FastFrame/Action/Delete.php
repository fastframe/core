<?php
/** $Id: Delete.php,v 1.2 2003/02/08 00:10:55 jrust Exp $ */
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

require_once dirname(__FILE__) . '/GenericForm.php';

// }}}
// {{{ class ActionHandler_Delete

/**
 * The ActionHandler_Delete:: class deletes the specified object 
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package ActionHandler 
 */

// }}}
class ActionHandler_Delete extends ActionHandler_GenericForm {
    // {{{ properties

    /**
     * The field to match up to the objectId in the where clause
     * @type string
     */
    var $deleteField = 'id';

    // }}}
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function ActionHandler_Delete()
    {
        ActionHandler_GenericForm::ActionHandler_GenericForm();
    }

    // }}}
    // {{{ run()
    
    /**
     * Deletes the object from the database 
     *
     * @access public
     * @return void
     */
    function run()
    {
        if ($this->o_application->deleteData($this->deleteField, FastFrame::getCGIParam('objectId', 'gp'))) {
            $o_output->setMessage($this->getSuccessMessage());
            $this->setSuccessActionId();
        }
        else {
            $o_output->setMessage($this->getProblemMessage());
            $this->setProblemActionId();
        }
        
        $this->takeAction();
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
        return _('There was an error when trying to delete the object.');
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
        $this->setActionId(ACTION_LIST);
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
        $this->setActionId(ACTION_LIST);
    }

    // }}}
}
?>
