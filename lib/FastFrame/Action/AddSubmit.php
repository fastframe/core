<?php
/** $Id: AddSubmit.php,v 1.1 2003/02/06 18:48:10 jrust Exp $ */
// {{{ requires

require_once dirname(__FILE__) . '/EditSubmit.php';

// }}}
// {{{ class ActionHandler_AddSubmit

/**
 * The ActionHandler_Edit:: class handles the processing of the form information and adds a
 * new record to the database. 
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
class ActionHandler_AddSubmit extends ActionHandler_EditSubmit {
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function ActionHandler_AddSubmit()
    {
        ActionHandler_EditSubmit::ActionHandler_EditSubmit();
    }

    // }}}
    // {{{ updateData()
        
    /**
     * Adds the data with the new form values.
     *
     * @access public
     * @return bool True if the insert succeeded, false otherwise
     */
    function updateData()
    {
        return $this->o_application->insertData($GLOBALS['_' . $this->formAction]);
    }

    // }}}
    // {{{ getSuccessMessage()

    /**
     * Gets the message for a successful add 
     *
     * @access public
     * @return string The message
     */
    function getSuccessMessage()
    {
        return _('Added the data successfully.');
    }

    // }}}
    // {{{ getProblemMessage()

    /**
     * Gets the message for when adding the data fails 
     *
     * @access public
     * @return string The message
     */
    function getProblemMessage()
    {
        return _('There was an error in adding your information.');
    }

    // }}}
    // {{{ setProblemActionId()

    /**
     * Sets the actionId to invoke when an error occurs in the update of the database. 
     *
     * @access public
     * @return void
     */
    function setProblemActionId()
    {
        $this->setActionId(ACTION_ADD);
    }

    // }}}
}
?>
