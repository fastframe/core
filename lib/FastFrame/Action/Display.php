<?php
/** $Id: Display.php,v 1.1 2003/02/06 18:48:10 jrust Exp $ */
// {{{ requires

require_once dirname(__FILE__) . '/GenericDisplay.php';

// }}}
// {{{ class ActionHandler_Display 

/**
 * The ActionHandler_Display:: class handles displaying some object, such as a table or
 * image to the user. 
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
class ActionHandler_Display extends ActionHandler_GenericAction {
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function ActionHandler_Display()
    {
        ActionHandler_GenericAction::ActionHandler_GenericAction();
    }

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
        $this->o_output->setMessage(_('Please choose an action from the menu.'), FASTFRAME_ERROR_MESSAGE);
        $this->output();
    }

    // }}}
}
?>
