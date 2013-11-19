<?php
/**
 * File List extension.
 *
 * Author: Jens Nyman <nymanjens.nj@gmail.com>
 *
 * This extension implements a new tag, <filelist>, which generates a list of
 * all images or other media that was uploaded to the page. Also, the tag adds
 * an input field to add a new file.
 *
 * Usage:
 *     <filelist/>
 *
 */

if (!defined('MEDIAWIKI')) die("Mediawiki not set");

/****************** EXTENSION ACTIONS ******************/
$wgExtensionMessagesFiles[ 'FileList' ] = __DIR__ . '/FileList.i18n.php';

/****************** CHANGING GLOBAL SETTINGS ******************/
/** Allow client-side caching of pages */
$wgCachePages       = false;
$wgCacheEpoch = 'date +%Y%m%d%H%M%S';

/** Set allowed extensions **/
$wgFileExtensions = array(
	'pdf','rar','zip','txt','7z','gz',
	'doc','ppt','xls',
	'docx','pptx','xlsx',
	'odt','odp','ods',
	'mws', 'm', 'cad', 'dwg', 'java',
	'jpg','jpeg','gif','png',
);
$wgVerifyMimeType = false;

/****************** EXTENSION SETTINGS ******************/
// configuration array of extension
$wgFileListConfig = array(
    'upload_anonymously' => false,
    'everyone_can_delete_files' => true,
);

// extension on the left corresponds with a .gif icon on the right
$fileListCorrespondingImages = array(
    'pdf' =>  'pdf', // .gif
    'rar' =>  'rar',
    '7z' =>   'rar',
    'gz' =>   'rar',
    'zip' =>  'zip',
    'txt' =>  'txt',
    'doc' =>  'doc',
    'docx' => 'doc',
    'ppt' =>  'ppt',
    'pptx' => 'ppt',
    'xls' =>  'xls',
    'xlsx' => 'xls',
    'odt' =>  'odt',
    'odp' =>  'odt',
    'ods' =>  'odt',
    'jpg' =>  'gif',
    'jpeg' => 'gif',
    'gif' =>  'gif',
    'png' =>  'gif',
);

// these will be opened in the browser when clicked on them
// all other will be forced a download
$fileListOpenInBrowser = array('pdf','txt','htm','html','css',
	'jpg','jpeg','bmp','gif','png');

/****************** SET HOOKS ******************/
// filelist tag
$wgExtensionFunctions[] = 'wfFileList';
// before upload: remove user info (ensure anonymity)
$wgHooks['UploadForm:BeforeProcessing'][] = 'fileListUploadBeforeProcessing';
// upload complete: redirect appropriately
$wgHooks['SpecialUploadComplete'][] = 'fileListUploadComplete';
// delete action
$wgHooks['UnknownAction'][] = 'actionDeleteFile';
// move page hook
$wgHooks['SpecialMovepageAfterMove'][] = 'fileListMovePage';
// credits
$wgExtensionCredits['parserhook'][] = array(
    'name'           => 'FileList',
    'author'         => 'Jens Nyman',
    'descriptionmsg' => 'fl_credits_desc',
    'url'            => 'http://www.mediawiki.org/wiki/Extension:FileList',
);

// internationalization file
require_once( dirname(__FILE__) . '/FileList.i18n.php' );

// functions
require_once( dirname(__FILE__) . '/library.php' );

/****************** FUNCTIONS ******************/
/**
 * Setup Medialist extension.
 * Sets a parser hook for <filelist/>.
 */
function wfFileList() {
    new FileList();
}

/**
 * Redirect to originating page after upload
 * 
 * @param UploadForm $form
 * @return boolean
 */
function fileListUploadComplete($form){
    $filename = $form->mDesiredDestName;
    $pos = strpos($filename, '_-_');
    if($pos === false)
        return true;
    // check if exam topic
    if(pagename_is_exam_page($filename))
        $pos += strlen(EXAM_STR);
    // get name
    $name = substr($filename, 0, $pos);
    $title = Title::newFromText($name);
    if(! $title->exists())
        return true;
    $nextpage = $title->getFullURL();
    global $wgOut;
    header( 'location: ' . $nextpage );
    exit;
}

/**
 * Remove user data to ensure anonymity
 * 
 * @param UploadForm $form
 * @return boolean
 */
function fileListUploadBeforeProcessing($form) {
    global $wgUser, $wgFileListConfig;
    if($wgFileListConfig['upload_anonymously'])
        $wgUser = User::newFromName( 'anonymous' );
    return true;
}

/**
 * Event handler for delete action
 * 
 * @param string $action
 * @param Article $article
 * @return boolean
 */
function actionDeleteFile( $action, $article ) {
    global $wgRequest, $wgOut;
    
    // check if this is the right action
    if( $action != 'deletefile' )
        return true;
    
    // set redirect params
    $wgOut->setSquidMaxage( 1200 );
    $wgOut->redirect( $article->getTitle()->getFullURL(), '301' );
    
    // get file to delete
    $filename = $wgRequest->getVal('file');
    
    // is user allowed to delete?
    if(!this_user_is_allowed_to_delete($filename))
        return false;
    
    // delete file
    $image = wfFindFile($filename);
    $image->delete('FileList deletefile action');
    
    return false;
}

/**
 * Reprefix files when moving (renaming) page
 * 
 * @param UploadForm $form
 * @param Title $old_title
 * @param Title $new_title
 * @return boolean
 */
function fileListMovePage($form, $old_title, $new_title) {
    // get vars
    $files = list_files_of_page($old_title);
    $old_prefix = get_prefix_from_page_name($old_title);
    $new_prefix = get_prefix_from_page_name($new_title);
    // foreach file that matches prefix --> rename
    foreach($files as $file) {
        $new_fname = $new_prefix . substr($file->img_name, strlen($old_prefix));
        $old_file = Title::newFromText('File:' . $file->img_name);
        $new_file = Title::newFromText('File:' . $new_fname);
        
        // move file
		$movePageForm = new MovePageForm($old_file, $new_file);
    	$movePageForm->reason = "";
		$movePageForm->doSubmit();
    }
    
    return true;
}

/****************** CLASSES ******************/
class FileList {
    /**
     * Setup Medialist extension.
     * Sets a parser hook for <filelist/>.
     */
    public function __construct() {
        global $wgParser;
        $wgParser->setHook('filelist', array(&$this, 'hookML'));
    } // end of constructor

    /**
     * The hook function. Handles <filelist/>.
     * 
     * @param string $headline: The tag's text content (between <filelist> and </filelist>)
     * @param string $argv: List of tag parameters
     * @param Parser $parser
     */
    public function hookML($headline, $argv, $parser) {
        // Get all files for this article
        $articleFiles = list_files_of_page($parser->mTitle);

        // Generate the media listing.
        $output = $this->outputMedia($parser->mTitle, $articleFiles);

        // Convert the listing wikitext into HTML and return it.
        //$localParser = new Parser();
        //$output = $localParser->parse($output, $parser->mTitle, $parser->mOptions);
        //$output = $output->getText()
        
        // Add form
        $output .= $this->outputForm($parser->mTitle);
        return $output;
    } // end of hookML

    /**
     * Generate output for the list.
     * 
     * @param string $pagename
     * @param array $filelist
     * @return string
     */
    function outputMedia($pageName, $filelist) {
        global $wgUser, $fileListCorrespondingImages, $wgFileListConfig;
        
        if( sizeof($filelist)  == 0 )
            return wfMsgForContent('fl_empty_list');
        
        $prefix = htmlspecialchars(get_prefix_from_page_name($pageName));
        $extension_folder_url = htmlspecialchars(get_index_url()) . 'extensions/' . basename(dirname(__FILE__)) . '/';
        $icon_folder_url = $extension_folder_url . 'icons/';
        
        $output = '';
        // style
        $output .= "<style>
                        /***** table ******/
                        table.noborder, table.noborder td, table.noborder tr {
                            border-width: 0;
                            padding: 0;
                            spacing: 0;
                        }
                        /***** buttons *****/
                        a.small_remove_button, a.small_edit_button {
                        	display: block;
                        	overflow: hidden;
                        	text-indent: -5000px;
                        	background-repeat: no-repeat;
                        	height: 11px;
                        	background-image: url($icon_folder_url/buttons_small_edit.gif);
                        }
                        a.small_remove_button {
                        	width: 11px;
                        	background-position: 0 0;
                        }
                        a.small_remove_button:hover {
                        	background-position: 0 -11px;
                        }
                        a.small_edit_button {
                        	width: 11px;
                        	background-position: -21px 0;
                        }
                        a.small_edit_button:hover {
                        	background-position: -21px -11px;
                        }
                    </style>";
                    
        // this is mandatory because parser interprets each whitespace at the start if a line
        $outputLines = explode("\n", $output);
        foreach($outputLines as &$line)
            $line = trim($line);
        $output = implode('', $outputLines);
        
        // check if exists
        $descr_column = false;
        foreach ($filelist as $dataobject) {
            $article = new Article ( Title::newFromText( 'File:'.$dataobject->img_name ) );
            $descr = $article->getContent();
            if(trim($descr) != "") {
                $descr_column = true;
                break;
            }
        }
        
        // table
        $output .= '<table class="wikitable">';
        $output .= '<tr>';
        $output .= '<th style="text-align: left">' . wfMsgForContent('fl_heading_name') . '</th>';
        $output .= '<th style="text-align: left">' . wfMsgForContent('fl_heading_datetime') . '</th>';
        $output .= '<th style="text-align: left">' . wfMsgForContent('fl_heading_size') . '</th>';
        if($descr_column)
            $output .= '<th style="text-align: left">' . wfMsgForContent('fl_heading_descr') . '</th>';
        if(!$wgFileListConfig['upload_anonymously'])
            $output .= '<th style="text-align: left">' . wfMsgForContent('fl_heading_user') . '</th>';
        $output .= '<th></th>';
        $output .= '</tr>';

        foreach ($filelist as $dataobject) {
                $output .= "<tr>";
                /** ICON PROCESSING **/
                $ext = file_get_extension($dataobject->img_name);
                if(isset($fileListCorrespondingImages[$ext]))
                    $ext_img = $fileListCorrespondingImages[$ext];
                else
                    $ext_img = 'default';
                $output .= '<td><img src="'.$icon_folder_url . $ext_img.'.gif" alt="" /> ';
                
                /** FILENAME PROCESSING**/
                $img_name = str_replace('_', ' ', $dataobject->img_name);
                $img_name = substr($img_name, strlen($prefix));
                $img_name_w_underscores = substr($dataobject->img_name, strlen($prefix));
                $link = $extension_folder_url . 'file.php?name='.urlencode($img_name_w_underscores) . "&file=" . urlencode($dataobject->img_name);
                // if description exists, use this as filename
                $descr = $dataobject->img_description;
                if($descr)
                    $img_name = $descr;
                $output .= '<a href="'.htmlspecialchars($link).'">'.htmlspecialchars($img_name).'</a></td>';
                
                /** TIME PROCESSING**/
                // converts (database-dependent) timestamp to unix format, which can be used in date()
                $timestamp = wfTimestamp(TS_UNIX, $dataobject->img_timestamp);
                $output .= '<td>' . time_to_string($timestamp) . "</td>";
                
                /** SIZE PROCESSING **/
                $size = human_readable_filesize($dataobject->img_size);
                $output .= "<td>$size</td>";
                
                /** DESCRIPTION **/
                if($descr_column) {
                    $article = new Article ( Title::newFromText( 'File:'.$dataobject->img_name ) );
                    $descr = $article->getContent();
                    $descr = str_replace("\n", " ", $descr);
                    $output .= '<td>'.htmlspecialchars($descr).'</td>';
                }
                
                /** USERNAME **/
                if(!$wgFileListConfig['upload_anonymously']) {
                    $output .= '<td>'.htmlspecialchars($dataobject->img_user_text).'</td>';
                }
                
                /** EDIT AND DELETE **/
                $output .= '<td><table class="noborder" cellspacing="2"><tr>';
                // edit
                $output .= sprintf('<td><acronym title="%s"><a href="%s" class="small_edit_button">' .
                                   '%s</a></acronym></td>',
                                   wfMsgForContent('fl_edit'),
                                   htmlspecialchars(page_link_by_title('File:'.$dataobject->img_name)),
                                   wfMsgForContent('fl_edit'));
                // delete
                if(this_user_is_allowed_to_delete($dataobject->img_name))
                    $output .= sprintf('<td><acronym title="%s">' .
                                       '<a href="%s?file=%s&action=deletefile" class="small_remove_button" ' .
                                       'onclick="return confirm(\''.wfMsgForContent('fl_delete_confirm').'\')">' .
                                       '%s</a></acronym></td>',
                                       wfMsgForContent('fl_delete'),
                                       htmlspecialchars($pageName->getFullURL()),
                                       htmlspecialchars(urlencode($dataobject->img_name)),
                                       htmlspecialchars($img_name),
                                       wfMsgForContent('fl_delete'));
                $output .= '</tr></table></td>';
                
                $output .= "</tr>";
        }
        $output .= '</table>';
        
        return $output;
    } // end of outputMedia

    /**
     * Generate output for the form.
     * 
     * @param string $pagename
     * @param array $filelist
     * @return string
     */
    function outputForm($pageName) {
        global $wgUser, $wgFileListConfig;
        
        $pageName = htmlentities($pageName);
        $prefix = htmlspecialchars(get_prefix_from_page_name($pageName));
        $form_action = htmlspecialchars(page_link_by_title('Special:Upload'));
        $upload_label = $wgFileListConfig['upload_anonymously'] ?
            wfMsgForContent('fl_upload_file_anonymously') : wfMsgForContent('fl_upload_file');
        $user_token = $wgUser->getEditToken();
        
        $output = '
            <script type="text/javascript">
                function fileListSubmit(){
                    form = document.filelistform;
                    filename = getNameFromPath(form.wpUploadFile.value);
                    if( filename == "" ) {
                        fileListError("'.wfMsgForContent('fl_empty_file').'");
                        return false;
                    }
                    form.wpDestFile.value = "'.$prefix.'" + filename;
                    return true;
                }
                function fileListError(message){
                    document.getElementById("filelist_error").innerHTML = message;
                }
                function getNameFromPath(strFilepath) {
                    var objRE = new RegExp(/([^\/\\\\]+)$/);
                    var strName = objRE.exec(strFilepath);
                 
                    if (strName == null) {
                        return null;
                    }
                    else {
                        return strName[0];
                    }
                }
            </script>
            <table class="wikitable" style="padding: 0; margin:0;"><tr><th>
                <div style="color: red;" id="filelist_error"></div>
                <form action="'.$form_action.'" method="post" name="filelistform" class="visualClear" enctype="multipart/form-data" id="mw-upload-form">
                <input name="wpUploadFile" type="file" />
                <input name="wpDestFile" type="hidden" value="" />
                <input name="wpWatchthis" type="hidden"/>
                <input name="wpIgnoreWarning" type="hidden" value="1" />
                <input type="hidden" value="Special:Upload" name="title" />
                <input type="hidden" name="wpDestFileWarningAck" />
                <input type="hidden" name="wpEditToken" value="'.$user_token.'" />
                <input type="submit" value="'.$upload_label.'" name="wpUpload" title="Upload [s]" accesskey="s"
                    class="mw-htmlform-submit" onclick="return fileListSubmit()" />
            </form></th></tr></table><br />';
        
        // this is mandatory because parser interprets each whitespace at the start if a line
        $outputLines = explode("\n", $output);
        foreach($outputLines as &$line)
            $line = trim($line);
        $output = implode('', $outputLines);
        return $output;
    } // end of outputForm
} // end of class FileList



