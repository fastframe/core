<?php
/** $Id: Action.php,v 1.1 2003/02/12 20:47:58 jrust Exp $ */
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

require_once dirname(__FILE__) . '/../ActionHandler.php';
require_once dirname(__FILE__) . '/../Application.php';
require_once dirname(__FILE__) . '/../Output.php';

// }}}
// {{{ class ActionHandler_Action

/**
 * The ActionHandler_Action:: class has methods and properties that are generally
 * useful to all actions. 
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package ActionHandler 
 */

// }}}
class ActionHandler_Action {
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
    function ActionHandler_Action()
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
