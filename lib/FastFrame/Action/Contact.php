<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2004 The Codejanitor Group                        |
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
// {{{ class FF_Action_Contact

/**
 * The FF_Action_Contact:: class Handles the setting up of the form
 * that is needed to send a contact message.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package ActionHandler
 */

// }}}
class FF_Action_Contact extends FF_Action_Form {
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
        $this->o_form->addElement('submit', 'submitbutton');
        $this->o_form->addElement('hidden', 'http_referer', @$_SERVER['HTTP_REFERER']);
        $this->o_form->addElement('text', 'name', null, array('maxlength' => 150, 'size' => 30));
        $this->o_form->addElement('text', 'subject', null, array('maxlength' => 150, 'size' => 30));
        $this->o_form->addElement('text', 'email', null, array('maxlength' => 150, 'size' => 30));
        $this->o_form->addElement('text', 'phone', null, array('maxlength' => 30, 'size' => 30));
        $this->o_form->addElement('textarea', 'message', null, array('cols' => 50, 'rows' => 10));
        $this->o_form->addRule('name', _('Name cannot be blank.'), 'required', null, 'client');
        $this->o_form->addRule('email', _('Invalid email address entered.'), 'email', null, 'client');
        $this->o_form->addRule('email', _('Email cannot be blank.'), 'required', null, 'client');
        $this->o_form->addRule('subject', _('Subject cannot be blank.'), 'required', null, 'client');
        $this->o_form->addRule('message', _('Message cannot be blank.'), 'required', null, 'client');
    }

    // }}}
    // {{{ renderSearchBox()

    /**
     * Renders the search box for this module.
     *
     * @access public
     * @return void
     */
    function renderSearchBox()
    {
        return;
    }

    // }}}
    // {{{ getSingularText()

    /**
     * Gets the text that describes a singular version of the items displayed
     *
     * @access public
     * @return string The singular text
     */
    function getSingularText()
    {
        return _('Contact');
    }

    // }}}
    // {{{ getFormConstants()

    /**
     * Gets the constant values to be filled into the forms.  Constants means it will
     * override GET/POST data with the same key. 
     *
     * @access public
     * @return mixed An array of the defaults or false if there was a problem 
     */
    function getFormConstants()
    {
        $a_const = array();
        if (FF_Request::getParam('submitWasSuccess', 'g', false)) {
            $a_const['subject'] = $a_const['message'] = '';
        }

        return array_merge(parent::getFormConstants(), $a_const);
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
            'title' => '* ' . _('Name:'),
			'titleStyle' => 'style="font-weight: bold;"',
            'data' => $this->o_renderer->elementToHtml('name'), 
        );
        $a_headers[] = array(
            'title' => '* ' . _('Email Address:'),
			'titleStyle' => 'style="font-weight: bold;"',
            'data' => $this->o_renderer->elementToHtml('email'), 
        );
        $a_headers[] = array(
            'title' => _('Phone:'),
            'data' => $this->o_renderer->elementToHtml('phone'), 
        );
        $a_headers[] = array(
            'title' => '* ' . _('Subject:'),
			'titleStyle' => 'style="font-weight: bold;"',
            'data' => $this->o_renderer->elementToHtml('subject'), 
        );
        $a_headers[] = array(
            'title' => '* ' . _('Message:'),
			'titleStyle' => 'style="font-weight: bold;"',
            'data' => $this->o_renderer->elementToHtml('message'), 
        );
        return $a_headers;
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
        return _('Send Help Message');
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
        $this->formActionId = ACTION_CONTACT_SUBMIT;
    }

    // }}}
}
?>
