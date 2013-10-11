<?php
/**
 * Functions file
 * 
 * Author: Jens Nyman <nymanjens.nj@gmail.com>
 * 
 */


/**
 * returns wether user is allowed to delete files
 * 
 * @param string $filename
 * @return bool
 */
function this_user_is_allowed_to_delete($filename){
    return true; // edit: everyone is allowed to delete file

    global $wgUser;
    $groups = $wgUser->getGroups();
    $username = $wgUser->getName();
    
    // get file user
    $image = wfFindFile($filename);
    $file_user = $image->getUser();
    
    // allowed to delete own files
    if($file_user == $username)
        return true;
    
    // admins can delete everything
    return in_array('sysop', $groups);
}

/**
 * Returns a human readable filesize
 *
 * @author      wesman20 (php.net)
 * @author      Jonas John
 * @version     0.3
 * @link        http://www.jonasjohn.de/snippets/php/readable-filesize.htm
 */
function human_readable_filesize($size) {
 
    // Adapted from: http://www.php.net/manual/en/function.filesize.php
 
    $mod = 1024;
 
    $units = explode(' ','B kB MB GB TB PB');
    for ($i = 0; $size > $mod; $i++) {
        $size /= $mod;
    }
 
    return round($size, 2) . ' ' . $units[$i];
}

