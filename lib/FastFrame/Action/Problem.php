<?php
/** $Id: Problem.php,v 1.1 2003/02/06 18:48:10 jrust Exp $ */
// {{{ requires

require_once dirname(__FILE__) . '/GenericProblem.php';

// }}}
// {{{ class ActionHandler_Problem 

/**
 * The ActionHandler_Problem:: class handles the occurrence of a problem on the
 * page. 
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
class ActionHandler_Problem extends ActionHandler_GenericAction {
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function ActionHandler_Problem()
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
        $this->o_output->setMessage($this->getProblemMessage(), FASTFRAME_ERROR_MESSAGE);
        $this->output();
    }

    // }}}
    // {{{ getProblemMessage()

    /**
     * Gets the problem message
     *
     * @access public
     * @return void
     */
    function getProblemMessage()
    {
        return _('A problem was encountered in processing your request.'), 
    }

    // }}}
}
?>
