<?php
/** $Id: GenericAction.php,v 1.2 2003/02/06 22:24:24 jrust Exp $ */
// {{{ requires

require_once dirname(__FILE__) . '/../ActionHandler.php';
require_once dirname(__FILE__) . '/../Application.php';
require_once dirname(__FILE__) . '/../Output.php';

// }}}
// {{{ class ActionHandler_GenericAction

/**
 * The ActionHandler_GenericAction:: class has methods and properties that are generally
 * useful to all actions. 
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
class ActionHandler_GenericAction {
    // {{{ properties

    /**
     * FastFrame_Registry instance
     * @type object
     */
    var $o_registry;

    /**
     * Output instance
     * @type object 
     */
    var $o_output;

    /**
     * ActionHandler instance
     * @type object
     */
    var $o_action;

    /**
     * Application instance
     * @type object
     */
    var $o_application;

    // }}}
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function ActionHandler_GenericAction()
    {
        $this->o_registry =& FastFrame_Registry::singleton();
        $this->o_output =& FastFrame_Output::singleton();
        $this->o_action =& ActionHandler::singleton();
    }

    // }}}
    // {{{ run()

    /**
     * Interface run class
     *
     * @access public
     * @returns void
     */
    function run()
    {
        return PEAR::raiseError(null, FASTFRAME_ERROR, null, E_USER_ERROR, "Run not implemented for this action!", 'FastFrame_Error', true);
    }

    // }}}
}
?>
