<?php
/** $Id: Add.php,v 1.1 2003/02/06 18:48:10 jrust Exp $ */
// {{{ requires

require_once dirname(__FILE__) . '/Edit.php';

// }}}
// {{{ class ActionHandler_Add

/**
 * The ActionHandler_Add:: class Handles the setting up of the form that is needed to
 * add an object.  Normally this takes the same course as editing an object.
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
class ActionHandler_Add extends ActionHandler_Edit {
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function ActionHandler_Add()
    {
        ActionHandler_Edit::ActionHandler_Edit();
    }

    // }}}
    // {{{ setSubmitActionId()

    /**
     * Sets the actionId that should be used when the form is submitted.
     *
     * @access public 
     * @returns void
     */
    function setSubmitActionId()
    {
        $this->setActionId(ACTION_ADD_SUBMIT);
    }

    // }}}
}
?>
