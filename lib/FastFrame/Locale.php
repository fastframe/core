<?php
/** $Id$ */
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
// | Authors: The Horde Team <http://www.horde.org>                       |
// |          Jason Rust <jrust@codejanitor.com>                          |
// +----------------------------------------------------------------------+

// }}}
// {{{ includes

require_once dirname(__FILE__) . '/Registry.php';
require_once dirname(__FILE__) . '/Auth.php';

// }}}
// {{{ class FF_Locale

/**
 * The FF_Locale:: class provides methods for providing locale-specific information.  This
 * includes methods for handling language detection and selection using gettext (NLS).
 *
 * @author  The Horde Team <http://www.horde.org/>
 * @author  Jason Rust <jrust@codejanitor.com>
 * @package FastFrame
 */

// }}}
class FF_Locale {
    // {{{ setLang()

    /**
     * Sets the language.
     *
     * @param string $in_lang (optional) The language abbreviation.
     *
     * @access public
     * @return void
     */
    function setLang($in_lang = null)
    {
        if (is_null($in_lang) || !FF_Locale::isValid($in_lang)) {
            $in_lang = FF_Locale::selectLang();
        }

        // First try language with the current charset
        $lang_charset = $in_lang . '.' . FF_Locale::getCharset();
        if ($lang_charset != setlocale(LC_ALL, $lang_charset)) {
            // Next try language with its default charset
            $a_nls = FF_Locale::getNLSData();
            $o_registry =& FF_Registry::singleton();
            $lang_charset = $in_lang . '.' . 
                (!empty($a_nls['charsets'][$in_lang]) ? $a_nls['charsets'][$in_lang] : 
                 $o_registry->getConfigParam('language/charset'));

            if ($lang_charset != setlocale(LC_ALL, $lang_charset)) {
                // Finally, try language solely.
                $lang_charset = $in_lang;
                setlocale(LC_ALL, $lang_charset);
            }
        }

        @putenv('LANG=' . $lang_charset);
        @putenv('LANGUAGE=' . $lang_charset);
    }

    // }}}
    // {{{ setTextdomain()

    /**
     * Sets the gettext domain.
     *
     * @param string $in_app The application name.
     * @param string $in_directory The directory where the application's LC_MESSAGES
     *               directory resides.
     * @param string $in_charset The charset.
     *
     * @access public
     * @return void
     */
    function setTextdomain($in_app, $in_directory, $in_charset)
    {
        bindtextdomain($in_app, $in_directory);
        textdomain($in_app);

        // The existence of this function depends on the platform.
        if (function_exists('bind_textdomain_codeset')) {
           bind_textdomain_codeset($in_app, $in_charset);
        }

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=' . $in_charset);
        }
    }

    // }}}
    // {{{ isValid()

    /**
     * Determines whether the supplied language is valid.
     *
     * @param string $in_language  The abbreviated name of the language.
     *
     * @access public
     * @return bool True if the language is valid, false if it's not valid or unknown.
     */
    function isValid($in_language)
    {
        $a_nls = FF_Locale::getNLSData();
        return !empty($a_nls['languages'][$in_language]);
    }

    // }}}
    // {{{ getCharset()

    /**
     * Return the charset for the current language.
     *
     * @access public
     * @return string  The character set that should be used with the current
     *                 locale settings.
     */
    function getCharset()
    {
        $lang_charset = setlocale(LC_ALL, 0);
        if (!strstr($lang_charset, ';')) {
            $lang_charset = explode('.', $lang_charset);
            if ((count($lang_charset) == 2) && !empty($lang_charset[1])) {
                return $lang_charset[1];
            }
        }

        $a_nls = FF_Locale::getNLSData();
        if (!empty($a_nls['charsets'][FF_Auth::getCredential('language')])) {
            return $a_nls['charsets'][FF_Auth::getCredential('language')];
        }
        else {
            $o_registry =& FF_Registry::singleton();
            return $o_registry->getConfigParam('language/charset');
        }
    }

    // }}}
    // {{{ getNLSData()

    /**
     * Includes the appropriate include files in order to return the lang array.
     *
     * @access public
     * @return array The FF_Locale array
     */
    function getNLSData()
    {
        static $a_nls;

        if (!isset($a_nls)) {
            $o_registry =& FF_Registry::singleton();
            $pth_lang = $o_registry->getRootFile('nls.php', 'config');
            if (@file_exists($pth_lang)) {
                include_once $pth_lang; 
            } 
            else {
                include_once $pth_lang . '.dist'; 
            }
        }

        return $a_nls;
    }

    // }}}
    // {{{ selectLang()

    /**
     * Selects the most preferred language for the current client session.
     *
     * @access public
     * @return string The selected language abbreviation.
     */
    function selectLang()
    {
        $o_registry =& FF_Registry::singleton();
        // First, check if language can be changed.
        if (!$o_registry->getConfigParam('language/allow_override')) {
            $s_lang = $o_registry->getConfigParam('language/default');
        }
        // See if we have the language stored in session
        elseif (!is_null(FF_Auth::getCredential('language'))) {
            $s_lang = FF_Auth::getCredential('language'); 
        } 
        // Try browser default
        elseif (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // The browser supplies a list, so return the first valid one.
            $a_nlss = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach ($a_nlss as $tmp_lang) {
                $tmp_lang = FF_Locale::_map(trim($tmp_lang));
                if (FF_Locale::isValid($tmp_lang)) {
                    $s_lang = $tmp_lang;
                    break;
                } 
                elseif (FF_Locale::isValid(FF_Locale::_map(substr($tmp_lang, 0, 2)))) {
                    $s_lang = FF_Locale::_map(substr($tmp_lang, 0, 2));
                    break;
                }
            }
        }
        else {
            $s_lang = $o_registry->getConfigParam('language/default');
        }

        return basename($s_lang);
    }

    // }}}
    // {{{ _map

    /**
     * Maps languages with common two-letter codes (such as nl) to the
     * full gettext code (in this case, nl_NL). Returns the language
     * unmodified if it isn't an alias.
     *
     * @param string $in_language  The language code to map.
     *
     * @access private
     * @return string  The mapped language code.
     */

    function _map($in_language)
    {
        $a_nls = FF_Locale::getNLSData();
        // First check if the untranslated language can be found
        if (isset($a_nls['aliases'][$in_language])) {
            return $a_nls['aliases'][$in_language];
        }

        // Translate the $language to get broader matches.
        // (eg. de-DE should match de_DE)
        $in_language = str_replace('-', '_', $in_language);
        $lang_parts = explode('_', $in_language);
        $in_language = strtolower($lang_parts[0]);
        if (isset($lang_parts[1])) {
            $in_language .= '_' . strtoupper($lang_parts[1]);
        }

        // First check if the untranslated language can be found
        if (isset($a_nls['aliases'][$in_language])) {
            return $a_nls['aliases'][$in_language];
        }

        // If we get that far down, the language cannot be found.
        return $in_language;
    }

    // }}}
}
