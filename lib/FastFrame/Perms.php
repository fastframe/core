<?php

// {{{ includes
require_once dirname(__FILE__) . '/Registry.php';
require_once dirname(__FILE__) . '/Perms/PermSource.php';

// }}}
// {{{ class FastFrame_Perms
/**
 *
 * A class for managing user permissions
 *
 * This class provides access to modify and view the permissions that
 * a FastFrame user has within the system.
 *
 * @author		Greg Gilbert <greg@treke.net>
 * @copyright	(c) 2003 by Brooks Institute of Photography
 * @version		$Id: Perms.php,v 1.1 2003/01/20 17:33:44 ggilbert Exp $
 * @package		FastFrame
 * @see			User Auth
 */
 // }}}
class FastFrame_Perms {
    // {{{ properties
    /**
     * User ID of the user associated with this permissions object
     * @var		string
     * @access	private
     */
    var $_userid;

    /**
     * Caches user permissions so we do not need to look them up
     * for each request
     * @var		array
     * @access	private
     */
    var $_permCache;

    /**
     * Provides an actual interface to the data source for permissions
     * @var		object
     * @access	private
     */
    var $_permSource;
    // //}}}
    // {{{ constructor
    /**
    *
    * Initializes the FastFrame_Perms class
    *
    * This function initializes the permissions class for the
    * for the specified user.
    *
    */
    function FastFrame_Perms(  $in_userID )
    {
        $this->_userID=$in_userID;

        $registry =& FastFrame_Registry::singleton();

        $a_source=$registry->getConfigParam('perms/source', null, 'FastFrameSESSID');
        $this->permSource=&PermSource::create($a_source['type'], $s_name, $a_source['params']);


    }
    // }}}
    // {{{ getAppList()
    /**
    *
    * Returns a list of apps the user has access to
    *
    * This function returns a list of FastFrame Apps that are 
    * installed in this framework that the user as LOGIN 
    * permissions for. 
    *
    */
    function getAppList ()
    {
        
        $registry =& FastFrame_Registry::singleton();

        if ($this->isSuperUser())
            return $registry->getApps();
        else
            return  $this->permSource->getAppList();
    }
    // }}}
    // {{{ getGroupList()
    /**
    *
    * Returns a list of groups that the user belongs to in the current app
    *
    * This function returns an array containting all groups that the current 
    * user has any permissions in
    *
    */
    functION getGroupList ()
    {
        return  $this->permSource->getGroupList();
    }
    // }}}
    // {{{ hasPerm()
    /**
    *
    * Check whether a user has the needed permission
    *
    * This function looks up whether a user is in a group
    * with the requested permissions in the current app.
    *
    */
    function hasPerms($in_perms)
    {

        $b_hasPerms=false;
        $s_app=FastFrame::getCurrentApp();

        if ($this->isSuperUser())
            return true;

        if (!is_array($this->_permCache[$s_app]) 
            && (count($this->_permCache[$s_app]) == 0 )) {

            // We dont have permissions cached, so we need to get them
            $this->_permCache[$s_app]=$this->_permSource->getPerms($s_app);

        }


        // $in_perms may be an array of permissions, or a single string
        if (is_array($in_perms)) {

            $i_foundCount=0;

            // Look for each of the requested permissions
            foreach ( $in_perms as $s_perm ) {
                if (in_array($this->_permCache[$s_app], $s_perm)) {
                    $i_foundCount++;
                }

                if ($i_foundCount==count($in_perms)) {
                    $b_hasPerms=true;
                }

            }
        }
        else {

            // Look up the one requested permission
            if (in_array($this->permCache[$s_app], $in_perms)) {
                $b_hasPerms=true;
            }

        }

        return $b_hasPerms;

    }
    // }}}
    

}
