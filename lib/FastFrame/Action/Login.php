<?php
/** $Id: Login.php,v 1.3 2003/02/06 22:25:12 jrust Exp $ */
// {{{ requires

require_once dirname(__FILE__) . '/GenericForm.php';
require_once dirname(__FILE__) . '/../Output/Table.php';

// }}}
// {{{ class ActionHandler_Login

/**
 * The ActionHandler_Login:: class handles the setting of a login page 
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
class ActionHandler_Login extends ActionHandler_GenericForm {
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function ActionHandler_Login()
    {
        ActionHandler_GenericForm::ActionHandler_GenericForm();
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
        $this->registerLoginJavascript();
        $this->setSubmitActionId();
        $this->o_form->setConstants($this->getFormConstants());
        $this->createFormElements();
        $o_table =& new FastFrame_Output_Table();
        $o_table->setTableHeaderText($this->getTableHeaderText());
        $o_table->setTableHeaders($this->getTableHeaders());
        $o_table->renderTwoColumnTable();
        $this->o_output->assignBlockCallback(array(&$this->o_form, 'toHtml'), array(), $o_table->getTableName());
        $this->registerSubmitRow($o_table);
        $this->o_output->output();
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
        $this->o_form->addElement('submit', 'submit', _('Submit'));
        $this->o_form->addRule('username', _('Username cannot be blank.'), 'required', null, 'client');
        $this->o_form->addRule('password', _('Password cannot be blank.'), 'required', null, 'client');
    }

    // }}}
    // {{{ registerLoginJavascript()
    
    /**
     * Registers the javascript for the login page to focus the element onload and capture
     * the keypress of the enter key.
     *
     * @access public
     * @return void
     */
    function registerLoginJavascript()
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
        $this->o_action->setActionId(ACTION_LOGIN_SUBMIT);
    }

    // }}}
    // {{{ getTableHeaders()

    /**
     * Gets the headers that we make a basic table out of.  Has the form of 'data' => the
     * cell data, 'title' => the cell description, 'titleStyles'/'dataStyles' => any
     * attributes to apply to the title and data cells respectively.
     *
     * @access public
     * @return array
     */
    function getTableHeaders()
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
    // {{{ getSubmitButton()

    /**
     * Gets the submit button
     *
     * @access public
     * @return string The html form element
     */
    function getSubmitButton()
    {
        return $this->o_form->renderElement('submit', true);
    }

    // }}}
}
?>
