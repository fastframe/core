<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2005 The Codejanitor Group                        |
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
// {{{ class FF_FileCache

/**
 * The FF_FileCache:: class provides methods for saving, retrieving, and
 * removing files from the data cache.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package FastFrame
 */

// }}}
class FF_FileCache {
    // {{{ properties
    
    /**
     * The registry object
     * @var object
     */
    var $o_registry;

    // }}}
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function FF_FileCache()
    {
        $this->o_registry =& FF_Registry::singleton();
    }

    // }}}
    // {{{ singleton()

    /**
     * To be used in place of the contructor to return any open instance.
     *
     * @access public
     * @return object FF_FileCache instance
     */
    function &singleton()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new FF_FileCache();
        }

        return $instance;
    }

    // }}}
    // {{{ save()

    /**
     * Saves a file to cache.
     *
     * @param string $in_data The file data
     * @param array $in_fileParts Array of data about how to save the
     *        file. 'name' (required name of file), 'subdir' (optional
     *        sub dir to put it in, 'id' (optional object id that these
     *        files are associated with) 
     * @param bool $in_useApp (optional) Save it into the current app
     *        dir?
     *
     * @access public
     * @return object A success object
     */
    function save($in_data, $in_fileParts, $in_useApp = false)
    {
        $o_result = new FF_Result();
        $this->checkDir($in_fileParts, $in_useApp);
        $s_path = $this->getPath($in_fileParts, $in_useApp);
        require_once 'File.php';
        $tmp_result = File::write($s_path, $in_data, FILE_MODE_WRITE);
        if (PEAR::isError($tmp_result)) {
            $o_result->setSuccess(false);
            $o_result->addMessage($tmp_result->getMessage());
        }

        File::close($s_path, FILE_MODE_WRITE);
        return $o_result;
    }

    // }}}
    // {{{ saveUploadedFile()

    /**
     * Saves an uploaded file to cache.  Does all the checks to make
     * sure it is a valid upload file and within the size limits.
     *
     * @param array $in_uploadData The data about the file from $_FILES
     * @param array $in_fileParts Array of data about how to save the
     *              file. 'name' (optoinal name of file if different
     *              than uploaded name), 'subdir' (optional sub dir
     *              to put it in, 'id' (optional object id that these
     *              files are associated with)
     * @param int $in_maxSize (optional) The max size of the file.  Pass
     *            0 for no max size.
     *  @param array $in_validTypes (optional) An array of valid file types.
     * @param bool $in_useApp (optional) Save it into the current app dir?
     *
     * @access public
     * @return object A success object
     */
    function saveUploadedFile($in_uploadData, $in_fileParts, $in_maxSize = 0, $in_validTypes = array(), $in_useApp = false)
    {
        $o_result = new FF_Result();
        // See if a file was sent
        if ($in_uploadData['error'] == 4 || empty($in_uploadData['name']) || $in_uploadData['name'] == 'none') {
            return $o_result;
        }

        if ($in_uploadData['size'] == 0) {
            $o_result->addMessage(_('The file you tried to attach contains no data.'));
            $o_result->setSuccess(false);
            return $o_result;
        }

        // Check size
        if ($in_maxSize > 0 && 
            ((isset($in_uploadData['error']) && 
              ($in_uploadData['error'] == 1 || $in_uploadData['error'] == 2)) ||
             $in_uploadData['size'] > $in_maxSize)) {
            require_once dirname(__FILE__) . '/Util.php';
            $o_result->addMessage(sprintf(_('The uploaded file %s is too big.  The maximum size is %s.'),
                        $in_uploadData['name'],
                        FF_Util::bytesToHuman($in_maxSize)));
            $o_result->setSuccess(false);
            return $o_result;
        }

        if (!is_uploaded_file($in_uploadData['tmp_name'])) {
            $o_result->addMessage(_('There was an error uploading the file.'));
            $o_result->setSuccess(false);
            return $o_result;
        }

        if (count($in_validTypes) > 0 && !in_array($in_uploadData['type'], $in_validTypes)) {
            $o_result->addMessage(_('The uploaded file was not a valid type.'));
            $o_result->setSuccess(false);
            return $o_result;
        }

        if (!isset($in_fileParts['name'])) {
            $in_fileParts['name'] = $in_uploadData['name'];
        }

        $this->checkDir($in_fileParts, $in_useApp);
        if (!move_uploaded_file($in_uploadData['tmp_name'], $this->getPath($in_fileParts, $in_useApp))) {
            $o_result->addMessage(_('Could not save the uploaded file.'));
            $o_result->setSuccess(false);
            return $o_result;
        }

        return $o_result;
    }

    // }}}
    // {{{ makeViewableFromWeb()
    
    /**
     * Makes the files in the cache directory viewable by web browsers.
     * By default access is denied to the files.
     *
     * @param array $in_fileParts Array of data about how to save the
     *        file. 'subdir' (requied sub dir to put it in), 'id'
     *        (optional object id that these files are associated with)
     * @param bool $in_useApp (optional) Save it into the current app
     *        dir?
     *
     * @access public
     * @return void
     */
    function makeViewableFromWeb($in_fileParts, $in_useApp = false)
    {
        $in_fileParts['name'] = '.htaccess';
        if (!$this->exists($in_fileParts, $in_useApp)) {
            $o_reult =& $this->save('Allow from all', $in_fileParts, $in_useApp);
        }
    }

    // }}}
    // {{{ exists()

    /**
     * Checks if a cache file exists
     *
     * @param array $in_fileParts Array of data about how to save the
     *        file. 'name' (required name of file), 'subdir' (optional
     *        sub dir to put it in, 'id' (optional object id that these
     *        files are associated with) 
     * @param bool $in_useApp (optional) Check in the current app dir?
     *
     * @access public
     * @return bool True if it exists, false otherwise
     */
    function exists($in_fileParts, $in_useApp = false)
    {
        $s_path = $this->getPath($in_fileParts, $in_useApp);
        // If it's a directory then don't test existence
        if (is_dir($s_path)) {
            return false;
        }

        return file_exists($s_path);
    }

    // }}}
    // {{{ getFilesForObject()

    /**
     * Returns all the files with the specified object.
     *
     * @param string $in_id The object id
     * @param string $in_filePath The subdir (i.e. ticket_files/)
     *
     * @access public
     * @return array The array of file names 
     */
    function getFilesForObject($in_id, $in_filePath)
    {
        $a_files = array();
        if ($handle = @opendir($this->getPath(array('subdir' => $in_filePath, 'id' => $in_id), true))) {
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..') {
                    $a_files[] = $file;
                }
            }

            closedir($handle);
        }

        return $a_files;
    }

    // }}}
    // {{{ remove()

    /**
     * Remove an uploaded file.
     *
     * @param array $in_fileParts Array of data about how to save the
     *        file. 'name' (required name of file), 'subdir' (optional
     *        sub dir to put it in, 'id' (optional object id that these
     *        files are associated with) 
     * @param bool $in_useApp (optional) Look in the current app dir?
     *
     * @access public
     * @return void
     */
    function remove($in_fileParts, $in_useApp = false)
    {
        $s_path = $this->getPath($in_fileParts, $in_useApp); 
        @unlink($s_path);
        // See if the id directory is now empty
        if (isset($in_fileParts['id']) && isset($in_fileParts['subdir'])) {
            $a_files = $this->getFilesForObject($in_fileParts['id'], $in_fileParts['subdir']);
            if (!count($a_files)) {
                @rmdir(dirname($s_path));
            }
        }
    }

    // }}}
    // {{{ getPath()

    /**
     * Gets the path to the cache file
     *
     * @param array $in_fileParts Array of data about how to save the
     *        file. 'name' (required name of file), 'subdir' (optional
     *        sub dir to put it in, 'id' (optional object id that these
     *        files are associated with) 
     * @param bool $in_useApp (optional) Check in the current app dir?
     * @param int $in_type (optional) What type of path to build, based
     *        on the constants (default is FASTFRAME_FILEPATH)
     *
     * @access public
     * @return string The path to the cache file
     */
    function getPath($in_fileParts, $in_useApp = false, $in_type = FASTFRAME_FILEPATH)
    {
        $s_app = $in_useApp ? $this->o_registry->getCurrentApp() . '/' : '';
        $s_path = '';
        $s_path .= isset($in_fileParts['subdir']) ? ($in_fileParts['subdir'] . '/') : '';
        $s_path .= isset($in_fileParts['id']) ? ($in_fileParts['id'] . '/') : '';
        $s_path .= $in_fileParts['name'];
        return $this->o_registry->getRootFile($s_app . $s_path, 'data', $in_type);
    }

    // }}}
    // {{{ checkDir()

    /**
     * Checks to make sure the directory is created for the cache file
     *
     * @param array $in_fileParts Array of data about how to save the
     *        file. 'name' (optional name of file), 'subdir' (optional
     *        sub dir to put it in, 'id' (optional object id that these
     *        files are associated with) 
     * @param bool $in_useApp (optional) Check inthe current app dir?
     *
     * @access public
     * @return void
     */
    function checkDir($in_fileParts, $in_useApp = false)
    {
        if (!isset($in_fileParts['name'])) {
            $in_fileParts['name'] = $in_fileParts['subdir'];
            unset($in_fileParts['subdir']);
            $s_dir = $this->getPath($in_fileParts, $in_useApp);
        }
        else {
            $s_dir = dirname($this->getPath($in_fileParts, $in_useApp));
        }

        if (!is_dir($s_dir)) {
            require_once 'System.php';
            if (!@System::mkdir("-p $s_dir"))  {
                trigger_error('Could not write to the FastFrame data directory.', E_USER_ERROR);
            }
        }
    }

    // }}}
    // {{{ isWebViewable()

    /**
     * Determines if the filetype can be viewed in a web browser
     *
     * @param string $in_name The file name
     *
     * @access public
     * @return bool True if it can, false otherwise
     */
    function isWebViewable($in_name)
    {
        $a_allowedTypes = array('image/bmp', 'image/png', 'image/jpeg', 'image/gif', 
                                'application/pdf', 
                                'text/css', 'text/plain', 'text/html', 'text/xml');
        return in_array($this->getContentType($in_name), $a_allowedTypes);
    }

    // }}}
    // {{{ isWebPlayable()

    /**
     * Determines if the filetype can be played in a web browser
     *
     * @param string $in_name The file name
     *
     * @access public
     * @return bool True if it can, false otherwise
     */
    function isWebPlayable($in_name)
    {
        $a_allowedTypes = array('audio/mp3', 'audio/x-realaudio', 'audio/x-pn-realaudio', 'audio/wav');
        return in_array($this->getContentType($in_name), $a_allowedTypes);
    }

    // }}}
    // {{{ getContentType()

    /**
     * Gets the content type based on the suffix of the filename
     *
     * @param string $in_filename The filename
     *
     * @access public
     * @return string The content type
     */
    function getContentType($in_filename)
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
                'csv' => 'text/plain',
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
