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

require_once dirname(__FILE__) . '/../Action.php';
require_once dirname(__FILE__) . '/../FileCache.php';

// }}}
// {{{ class FF_Action_Download 

/**
 * The FF_Action_Download:: class handles pushing a download of a
 * specified file from the file cache.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package Action 
 */

// }}}
class FF_Action_Download extends FF_Action {
    // {{{ run()
    
    /**
     * Sends out the file to be downloaded.
     *
     * @access public
     * @return object The next action object
     */
    function run()
    {
        $o_fileCache =& FF_FileCache::singleton();
        if (!$o_fileCache->exists(FF_Request::getParam('objectId', 'g'), FF_Request::getParam('useApp', 'g', false))) {
            $this->o_output->setMessage(_('The specified file could not be found.'), FASTFRAME_ERROR_MESSAGE, true);
            $this->o_nextAction->setActionId(ACTION_PROBLEM);
            return $this->o_nextAction;
        }

        $s_file = $o_fileCache->getPath(FF_Request::getParam('objectId', 'g'), FF_Request::getParam('useApp', 'g', false));
        $s_name = basename($s_file);
        if (!FastFrame::isEmpty(FF_Request::getParam('prefix', 'g'))) {
            $s_name = substr($s_name, strlen(FF_Request::getParam('prefix', 'g')));
        }

        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        if (!FF_Request::getParam('displayFile', 'g', false)) {
            header('Content-Type: application/force-download');
            header('Content-Disposition: attachment; filename="' . $s_name . '"');
            header('Content-Description: File Transfer');
        }
        else {
            header('Content-Type: ' . $this->_getContentType($s_name));
        }

        header('Accept-Ranges: bytes');
        header('Content-Length: ' . filesize($s_file));
        @readfile($s_file);
        exit;
    }

    // }}}
    // {{{ _getContentType()

    /**
     * Gets the content type based on the suffix of the filename
     *
     * @param string $in_filename The filename
     *
     * @access private
     * @return The content type
     */
    function _getContentType($in_filename)
    {
        // {{{ mime types

        $a_mime = array(
                'xxx' => 'document/unknown',
                '3gp' => 'video/quicktime',
                'ai' => 'application/postscript',
                'aif' => 'audio/x-aiff',
                'aiff' => 'audio/x-aiff',
                'aifc' => 'audio/x-aiff',
                'applescript' => 'text/plain',
                'asc' => 'text/plain',
                'au' => 'audio/au' ,
                'avi' => 'video/x-ms-wm',
                'bmp' => 'image/bmp',
                'cs' => 'application/x-csh',
                'css' => 'text/css',
                'dv' => 'video/x-dv',
                'doc' => 'application/msword',
                'dif' => 'video/x-dv',
                'eps' => 'application/postscript',
                'gif' => 'image/gif',
                'gtar' => 'application/x-gtar',
                'gz' => 'application/g-zip',
                'gzip' => 'application/g-zip',
                'h' => 'text/plain',
                'hqx' => 'application/mac-binhex40',
                'html' => 'text/html',
                'htm' => 'text/html',
                'jpe' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'jpg' => 'image/jpeg',
                'js' => 'application/x-javascript',
                'latex' => 'application/x-latex',
                'm' => 'text/plain',
                'mov' => 'video/quicktime',
                'movie' => 'video/x-sgi-movie',
                'm3u' => 'audio/x-mpegurl',
                'mp3' => 'audio/mp3',
                'mp4' => 'video/mp4',
                'mpeg' => 'video/mpeg',
                'mpe' => 'video/mpeg',
                'mpg' => 'video/mpeg',
                'pct' => 'image/pict',
                'pdf' => 'application/pdf',
                'php' => 'text/plain',
                'pic' => 'image/pict',
                'pict' => 'image/pict',
                'png' => 'image/png',
                'ppt' => 'application/vnd.ms-powerpoint',
                'ps' => 'application/postscript',
                'qt' => 'video/quicktime',
                'ra' => 'audio/x-realaudio',
                'ram' => 'audio/x-pn-realaudio',
                'rm' => 'audio/x-pn-realaudio',
                'rtf' => 'text/rtf',
                'rtx' => 'text/richtext',
                'sh' => 'application/x-sh',
                'sit' => 'application/x-stuffit',
                'smi' => 'application/smil',
                'smil' => 'application/smil',
                'swf' => 'application/x-shockwave-flash',
                'tar' => 'application/x-tar',
                'tif' => 'image/tiff',
                'tiff' => 'image/tiff',
                'tex' => 'application/x-tex',
                'texi' => 'application/x-texinfo',
                'texinfo' => 'application/x-texinfo',
                'tsv' => 'text/tab-separated-values',
                'txt' => 'text/plain',
                'wav' => 'audio/wav',
                'wmv' => 'video/x-ms-wmv',
                'asf' => 'video/x-ms-asf',
                'xls' => 'application/vnd.ms-excel',
                'xml' => 'text/xml',
                'xsl' => 'text/xml',
                'zip' => 'application/zip');

        // }}}
        $a_pth = pathinfo(strtolower($in_filename));
        if (!isset($a_pth['extension'])) {
            $a_pth['extension'] = 'xxx';
        }

        return isset($a_mime[$a_pth['extension']]) ? $a_mime[$a_pth['extension']] : $a_mime['xxx'];
    }

    // }}}
}
?>
