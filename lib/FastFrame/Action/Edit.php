<?php
/** $Id: Edit.php,v 1.2 2003/02/08 00:10:55 jrust Exp $ */
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
// {{{ class ActionHandler_Edit

/**
 * The ActionHandler_Edit:: class Handles the setting up of the form that is needed to
 * edit an object.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package ActionHandler 
 */

// }}}
class ActionHandler_Edit extends ActionHandler_GenericForm {
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function ActionHandler_Edit()
    {
        ActionHandler_GenericForm::ActionHandler_GenericForm();
    }

    // }}}
    // {{{ run()
    
    /**
     * Sets up the edit form.
     *
     * @access public
     * @return void
     */
    function run()
    {
        $this->o_form->addElement('hidden', 'objectId');
        $this->beginFormTable();
        $this->processTableHeaders();
        $this->registerSubmitRow();
    }

    // }}}
    // {{{ getSubmitButton()

    /**
     * Gets the submit button
     *
     * @access public
     * @return string The html form element
     */
    function getSubmitButton()
    {
        $this->o_form->addElement('submit', 'submit', _('Update'));
        return $this->o_form->renderElement('submit', true);
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
        $this->setActionId(ACTION_EDIT_SUBMIT);
    }

    // }}}
}
?>
