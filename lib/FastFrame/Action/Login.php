<?php
/** $Id: Login.php,v 1.8 2003/03/15 01:26:57 jrust Exp $ */
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

require_once dirname(__FILE__) . '/Form.php';

// }}}
// {{{ class FF_Action_Login

/**
 * The FF_Action_Login:: class handles the setting of a login page 
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package ActionHandler 
 */

// }}}
class FF_Action_Login extends FF_Action_Form {
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @param object $in_model The model object
     *
     * @access public
     * @return void
     */
    function FF_Action_Login(&$in_model)
    {
        FF_Action_Form::FF_Action_Form($in_model);
    }

    // }}}
    // {{{ run()
    
    /**
     * Sets up the login form.
     *
     * @access public
     * @return void
     */
    function run()
    {
        $this->o_output->setMenuType('none');
        $this->o_output->setPageType('smallTable');
        $this->o_output->setPageName($this->getPageName());
        $this->setDefaultMessage();
        $this->renderLoginJavascript();
        $this->setSubmitActionId();
        $a_constants = $this->getFormConstants();
        if ($a_constants === false) {
            return $this->o_nextAction;
        }

        $this->o_form->setConstants($a_constants);
        $this->createFormElements();
        $this->renderFormTable();
        $this->o_output->output();
        return $this->o_nextAction;
    }

    // }}}
    // {{{ createFormElements()

    /**
     * Creates the form elements needed for the form and any rules that apply to those
     * elements. 
     *
     * @access public
     * @return void
     */
    function createFormElements()
    {
        $this->o_form->addElement('hidden', 'actionId');
        // filled in from where we came from
        $this->o_form->addElement('hidden', 'loginRedirect');
        $this->o_form->addElement('text', 'username', null, array('maxlength' => 25, 'style' => 'width: 100px;'));
        $this->o_form->addElement('password', 'password', null, array('maxlength' => 25, 'style' => 'width: 100px;'));
        $this->o_form->addElement('submit', 'submit', _('Login'));
        $this->o_form->addRule('username', _('Username cannot be blank.'), 'required', null, 'client');
        $this->o_form->addRule('password', _('Password cannot be blank.'), 'required', null, 'client');
    }

    // }}}
    // {{{ renderLoginJavascript()
    
    /**
     * Registers the javascript for the login page to focus the element onload and capture
     * the keypress of the enter key.
     *
     * @access public
     * @return void
     */
    function renderLoginJavascript()
    {
        ob_start();
        ?>
        <script language="JavaScript" type="text/javascript">
        <!--
        // {{{ setFocus()

        /** set the focus on the form elements */
        function setFocus()
        {
            if (document.<?php echo $this->formName; ?>.username.value == '') {
                document.<?php echo $this->formName; ?>.username.focus();
            } 
            else {
                document.<?php echo $this->formName; ?>.password.focus();
            }
        }

        // }}}
        // {{{ enterKeyTrap()

        /** catches the enter key to submit the form */
        function enterKeyTrap(e)
        {
            var keyPressed;

            if (document.layers) {
                keyPressed = String.fromCharCode(e.which);
            } 
            else if (document.all) {
                keyPressed = String.fromCharCode(window.event.keyCode);
            } 
            else if (document.getElementById) {
                keyPressed = String.fromCharCode(e.keyCode);
            }

            if ((keyPressed == "\r" || keyPressed == "\n")) {
                return validate_<?php echo $this->formName; ?>();
            }
        }

        // Setup the enter keytrap code
        if (window.document.captureEvents != null) {
            window.document.captureEvents(Event.KEYPRESS);
            window.document.onkeypress = enterKeyTrap;
        }

        // }}}
        addOnload(setFocus);
        //-->
        </script>
        <?php
        $this->o_output->assignBlockData(array('T_javascript' => ob_get_contents()), 'javascript');
        ob_end_clean();
    }
    
    // }}}
    // {{{ setDefaultMessage()

    /**
     * Sets the default message for the login page
     *
     * @access public
     * @return void
     */
    function setDefaultMessage()
    {
        $this->o_output->setMessage(_('Please enter your username and password to login'));
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
        $this->formActionId = ACTION_LOGIN_SUBMIT;
    }

    // }}}
    // {{{ getTableData()

    /**
     * Gets the headers that we make a basic table out of.  Has the form of 'data' => the
     * cell data, 'title' => the cell description, 'titleStyles'/'dataStyles' => any
     * attributes to apply to the title and data cells respectively.
     *
     * @access public
     * @return array
     */
    function getTableData()
    {
        $a_headers = array();
        $a_headers[] = array(
            'title' => _('Username:'),
            'data' => $this->o_form->renderElement('username', true), 
            'titleStyle' => '',
            'dataStyle' => '',
        );
        $a_headers[] = array(
            'title'    => _('Password'),
            'data' => $this->o_form->renderElement('password', true), 
            'titleStyle' => '',
            'dataStyle' => '',
        );
        return $a_headers;
    }

    // }}}
    // {{{ getPageName()

    /**
     * Returns the page name for this action 
     *
     * @access public
     * @return string The page name
     */
    function getPageName()
    {
        return _('Login');
    }

    // }}}
    // {{{ getTableHeaderText()
    
    /**
     * Gets the description of this form to be placed in the table header 
     *
     * @access public
     * @return string The text for the header.
     */
    function getTableHeaderText()
    {
        return _('Login');
    }

    // }}}
}
?>
