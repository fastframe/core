<?php
/** $Id: Delete.php,v 1.1 2003/02/06 18:48:10 jrust Exp $ */
// {{{ requires

require_once dirname(__FILE__) . '/GenericForm.php';

// }}}
// {{{ class ActionHandler_Delete

/**
 * The ActionHandler_Delete:: class deletes the specified object 
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lesser.html.
 *
 * @author  Jason Rust <jrust@rustyparts.com>
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
