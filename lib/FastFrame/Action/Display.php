<?php
/** $Id: Display.php,v 1.3 2003/02/08 00:10:55 jrust Exp $ */
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

require_once dirname(__FILE__) . '/GenericDisplay.php';

// }}}
// {{{ class ActionHandler_Display 

/**
 * The ActionHandler_Display:: class handles displaying some object, such as a table or
 * image to the user. 
 *
 * @author  Jason Rust <jrust@codejanitor.com>
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
        $this->o_output->output();
    }

    // }}}
}
?>
