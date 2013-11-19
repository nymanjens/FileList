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
function fl_this_user_is_allowed_to_delete($filename){
    global $wgUser, $wgFileListConfig;

    // apply everyone_can_delete_files setting if it is true
    if($wgFileListConfig['everyone_can_delete_files'])
        return true;

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
function fl_human_readable_filesize($size) {
 
    // Adapted from: http://www.php.net/manual/en/function.filesize.php
 
    $mod = 1024;
 
    $units = explode(' ','B kB MB GB TB PB');
    for ($i = 0; $size > $mod; $i++) {
        $size /= $mod;
    }
 
    return round($size, 2) . ' ' . $units[$i];
}

/**
 * get page URL
 *
 * @param string $title
 * @return string
 */
function fl_page_link_by_title($title, $query = ''){
    $title = Title::newFromText($title);
    return $title->getFullURL($query);
}

/**
 * get wiki root url (for example for linking to images)
 *
 * @param string $title
 * @return string
 */
function fl_get_index_url(){
    $title = Title::newMainPage();
    return dirname($title->getFullURL("query=")) . '/';
}

/**
 * Userfriendly time string
 * 
 * @param int $time
 * @return string
 */
function fl_time_to_string($time){
    if(date('Y-m-d', $time) == date('Y-m-d') )
        return date("H:i", $time);
    if(date('z')-1 == date('z',$time) && date('Y') == date('Y',$time) )
        return fl_translate_time("Yesterday").", ".date("H:i", $time);
    if(date('z')-6 <= date('z',$time) && date('Y') == date('Y',$time) )
        return fl_translate_time(date("D", $time)) . date(", H:i", $time);
    if(time() - $time < 60*60*24*50)
        return fl_translate_time(date("D", $time)) . date(", j ", $time) . strtolower(fl_translate_time(date("M", $time)));
    if(date('y', $time) == date('y'))
        return date("j ", $time) . strtolower(fl_translate_time(date("M", $time))) . date(" 'y", $time);
    return fl_translate_time(date("M", $time)) . date(" 'y", $time);
}

/**
 * translate to Dutch (used for fl_time_to_string)
 *
 * @param string $word
 * @return string
 */
function fl_translate_time($word) {
    global $wgLanguageCode;
    $translate_array = array();
    $translate_array['nl'] = array(
        'Today' => 'Vandaag',
        'Yesterday' => 'Gisteren',
        'Mon' => 'Ma',
        'Tue' => 'Di',
        'Wed' => 'Woe',
        'Thu' => 'Do',
        'Fri' => 'Vrij',
        'Sat' => 'Za',
        'Sun' => 'Zo',
        'Mar' => 'Mrt',
        'May' => 'Mei',
        'Oct' => 'Okt',
        'January'   => 'januari',
        'February'  => 'februari',
        'March'     => 'maart',
        'April'     => 'april',
        'May'       => 'mei',
        'June'      => 'juni',
        'July'      => 'juli',
        'August'    => 'augustus',
        'September' => 'september',
        'October'   => 'oktober',
        'November'  => 'november',
        'December'  => 'december',
    );
    $translate_array['fr'] = array(
        'Today' => 'Aujourd\'hui',
        'Yesterday' => 'Hier',
        'Mon' => 'Lun',
        'Tue' => 'Mar',
        'Wed' => 'Mer',
        'Thu' => 'Jeu',
        'Fri' => 'Ven',
        'Sat' => 'Sam',
        'Sun' => 'Dim',
        'Feb' => 'Fév',
        'Apr' => 'Avr',
        'May' => 'Mai',
        'Jun' => 'Juin',
        'Jul' => 'Juil',
        'Aug' => 'Aoû',
        'Dec' => 'Déc',
        'January' => 'janvier',
        'February' => 'février',
        'March' => 'mars',
        'April' => 'avril',
        'May' => 'mai',
        'June' => 'juin',
        'July' => 'juillet',
        'August' => 'août',
        'September' => 'septembre',
        'October' => 'octobre',
        'November' => 'novembre',
        'December' => 'décembre',
    );
    $translate_array['sv'] = array(
        'Today' => 'i dag',
        'Yesterday' => 'i går',
        'Mon' => 'Mån',
        'Tue' => 'Tis',
        'Wed' => 'Ons',
        'Thu' => 'Tor',
        'Fri' => 'Fre',
        'Sat' => 'Lör',
        'Sun' => 'Sön',
        'May' => 'Maj',
    );
    
    if( isset($translate_array[$wgLanguageCode][$word]) && $translate_array[$wgLanguageCode][$word] != '')
        return $translate_array[$wgLanguageCode][$word];
    else return $word;
}

/**
 * get file extension
 * 
 * @param string $path
 * @return string
 */
function fl_file_get_extension($filepath) {
    preg_match('/[^?]*/', $filepath, $matches);
    $string = $matches[0];
    $pattern = preg_split('/\./', $string, -1, PREG_SPLIT_OFFSET_CAPTURE);
    // check if there is any extension
    if(count($pattern) == 1) {
        return "";
    }
    if(count($pattern) > 1) {
        $filenamepart = $pattern[count($pattern)-1][0];
        preg_match('/[^?]*/', $filenamepart, $matches);
        return $matches[0];
    }
}

/**
 * list files of page
 * 
 * @param string $pagename
 * @return array
 */
function fl_list_files_of_page($pagename) {
    // Query the database.
    $dbr =& wfGetDB(DB_SLAVE);
    $res = $dbr->select(
        array('image'),
        array('img_name','img_media_type','img_user_text','img_description', 'img_size',
              'img_timestamp','img_major_mime','img_minor_mime'),
        '',
        '',
        array('ORDER BY' => 'img_timestamp')
        );
    if ($res === false)
        return array();

    // Convert the results list into an array.
    $list = array();
    $prefix = fl_get_prefix_from_page_name($pagename);
    while ($x = $dbr->fetchObject($res)) {
        if( strtolower(substr($x->img_name, 0, strlen($prefix))) == strtolower($prefix)) {
            $list[] = $x;
        }
    }

    // Free the results.
    $dbr->freeResult($res);

    return $list;
}

/**
 * get prefix from page name
 * 
 * @param string $pagename
 * @return string
 */
function fl_get_prefix_from_page_name($pageName) {
    $pageName = str_replace(' ', '_', $pageName);
    return $pageName . '_-_';
}

function fl_strip_accents($stripAccents){
    return trim(str_replace(array("'", "%", "?", "!", ":", ",", "*"), "", iconv("utf-8", "ascii//TRANSLIT", $stripAccents)));
}
