<?php
/**
* @Credits Retro-Neptune-Bigjoos
* @Project TBDev.net 09 takeedit.php
* @Category Addon Mods
* @Date Monday, Aug 2, 2010
*/
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'bittorrent.php');
require_once(INCL_DIR.'user_functions.php');
require_once(INCL_DIR.'page_verify.php');
define('MIN_CLASS', UC_STAFF);
define('NFO_SIZE', 65535);
dbconn();
loggedinorreturn();

$lang = array_merge( load_language('global'), load_language('takeedit') );
$newpage = new page_verify(); 
$newpage->check('teit');

$possible_extensions = array('nfo', 'txt');
if (!mkglobal('id:name:descr:type')) 
die();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ( !is_valid_id($id) )
stderr($lang['takedit_failed'], $lang['takedit_no_data']);

/**
*
* @Function valid_torrent_name
* @Notes only safe characters are allowed..
* @Begin
*/
function valid_torrent_name($torrent_name)
{
    $allowedchars = 'abcdefghijklmnopqrstuvwxyz ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789.-_';
    for ($i = 0; $i < strlen($torrent_name); ++$i)
    if (strpos($allowedchars, $torrent_name[$i]) === false)
        return false;
    return true;
}
/**
*
* @Function is_valid_url
* @Begin
*/
if (!function_exists('is_valid_url')) {
    function is_valid_url($link)
    {
        return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $link);
    }
}
/**
*
* @Function is_valid_url
* @End
*/
$nfoaction='';

$select_torrent = @sql_query('SELECT name, descr, category, visible, release_group, poster, url, anonymous, sticky, owner, allow_comments, nuked, nukereason, filename, save_as FROM torrents WHERE id = '.sqlesc($id)) or sqlerr(__FILE__, __LINE__);
$fetch_assoc = mysql_fetch_assoc($select_torrent) or stderr('Error', 'No torrent with this ID!');

if ($CURUSER['id'] != $fetch_assoc['owner'] && $CURUSER['class'] < MIN_CLASS)
stderr('You\'re not the owner!', 'How did that happen?');

$updateset = array();

$fname = $fetch_assoc['filename'];
preg_match('/^(.+)\.torrent$/si', $fname, $matches);
$shortfname = $matches[1];
$dname = $fetch_assoc['save_as'];

if ((isset($_POST['nfoaction'])) && ($_POST['nfoaction'] == 'update')) {
    if (empty($_FILES['nfo']['name']))
        stderr('Updated failed', 'No NFO!');

    if ($_FILES['nfo']['size'] == 0)
        stderr('Updated failed', '0-byte NFO!');

    if (!preg_match('/^(.+)\.[' . join(']|[', $possible_extensions) . ']$/si', $_FILES['nfo']['name']))
        stderr('Updated failed', 'Invalid extension. <b>' . join(', ', $possible_extensions) . '</b> only!', false);

    if (!empty($_FILES['nfo']['name']) && $_FILES['nfo']['size'] > NFO_SIZE)
        stderr('Updated failed', 'NFO is too big! Max ' . number_format(NFO_SIZE) . ' bytes!');

    if (@is_uploaded_file($_FILES['nfo']['tmp_name']) && @filesize($_FILES['nfo']['tmp_name']) > 0)
        $updateset[] = "nfo = " . sqlesc(str_replace("\x0d\x0d\x0a", "\x0d\x0a", file_get_contents($_FILES['nfo']['tmp_name'])));
    } else
    if ($nfoaction == "remove")
    $updateset[] = "nfo = ''";

    //== Make sure they do not forget to fill these fields :D
    foreach(array($descr,$type,$name) as $x) {
        if(empty($x))
        stderr("Error", $lang['takedit_no_data']);
    }
    if (isset($_POST['name']) && (($name = $_POST['name']) != $fetch_assoc['name']) && valid_torrent_name($name)){
        $updateset[] = 'name = ' . sqlesc($name);
    $updateset[] = 'search_text = ' . sqlesc(searchfield("$shortfname $dname $torrent"));
    }
    if (isset($_POST['descr']) && ($descr = $_POST['descr']) != $fetch_assoc['descr']){
        $updateset[] = 'descr = ' . sqlesc($descr);
    $updateset[] = 'ori_descr = ' . sqlesc($descr);
    }
    if (isset($_POST['type']) && (($category = 0 + $_POST['type']) != $fetch_assoc['category']) && is_valid_id($category)){
        $updateset[] = 'category = ' . sqlesc($category);
        }
	  ///////////////////////////////
	  if (($visible = (isset($_POST['visible']) != ''?'yes':'no')) != $fetch_assoc['visible']){
        $updateset[] = 'visible = ' . sqlesc($visible);
   }
   if ($CURUSER['class'] > UC_STAFF)
   {
   if (isset($_POST["banned"]))
   {
   $updateset[] = "banned = 'yes'";  
   $_POST["visible"] = 0;
   } else
   $updateset[] = "banned = 'no'";
   }
    /**
    *
    * @Custom Mods
    * 
    */
    // ==09 Sticky torrents 
    if($CURUSER['class'] > UC_STAFF){
    if (($sticky = (isset($_POST['sticky']) != ''?'yes':'no')) != $fetch_assoc['sticky']){
    $updateset[] = 'sticky = ' . sqlesc($sticky);
    }
    } 
    // ==09 Simple nuke/reason mod 
    if (isset($_POST['nuked']) && ($nuked = $_POST['nuked']) != $fetch_assoc['nuked']){
        $updateset[] = 'nuked = ' . sqlesc($nuked);
        }
    if (isset($_POST['nukereason']) && ($nukereason = $_POST['nukereason']) != $fetch_assoc['nukereason']){
        $updateset[] = 'nukereason = ' . sqlesc($nukereason);
        }
    // ==09 Poster Mod
    if (isset($_POST['poster']) && (($poster = $_POST['poster']) != $fetch_assoc['poster'] && !empty($poster))){
        if (!preg_match("/^http:\/\/[^\s'\"<>]+\.(jpg|gif|png)$/i", $poster))
            stderr('Updated failed', 'Poster MUST be in jpg, gif or png format. Make sure you include http:// in the URL.');
        $updateset[] = 'poster = ' . sqlesc($poster);
        }
        //==09 Set Freeleech on Torrent Time Based
        if (isset($_POST['free_length']) && ($free_length = 0 + $_POST['free_length']))
        {
        if ($free_length == 255)
            $free = 1;

        elseif ($free_length == 42)
            $free = (86400 + time());

        else
            $free = (time() + $free_length * 604800);

        $updateset[] = "free = ".sqlesc($free);
        write_log("Torrent $id ($name) set Free for ".($free != 1 ? "Until ".get_date($free, 'DATE') : 'Unlimited')." by $CURUSER[username]");
        }
    
        if (isset($_POST['fl']) && ($_POST['fl'] == 1))
        {
        $updateset[] = "free = '0'";
        write_log("Torrent $id ($name) No Longer Free. Removed by $CURUSER[username]");
        }
        /// end freeleech mod
        // ===09 Allowcomments
        if ((isset($_POST['allow_comments'])) && (($allow_comments = $_POST['allow_comments']) != $fetch_assoc['allow_comments'])) {
            if ($CURUSER['class'] >= UC_STAFF && $CURUSER['class'] <= UC_SYSOP)
                $updateset[] = "allow_comments = " . sqlesc($allow_comments);
        } else
            $updateset[] = "allow_comments = 'yes'";
        // ===end
        //==09 Imdb mod
        if (isset($_POST['url']) && (($url = $_POST['url']) != $fetch_assoc['url'] && !empty($url))){
            if (!preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url))
                stderr('Updated failed', 'Make sure you include http:// in the URL.');
            $updateset[] = 'url = ' . sqlesc($url);
            }  
                //==09 Anonymous torrents
                if (($anonymous = (isset($_POST['anonymous']) != ''?'yes':'no')) != $fetch_assoc['anonymous']){
                $updateset[] = 'anonymous = ' . sqlesc($anonymous);
                }
                //==Release group
                
                $release_group_choices = array('scene' => 1, 'p2p' => 2, 'none' => 3);{
                $release_group = (isset($_POST['release_group']) ? $_POST['release_group'] : 'none');
                if (isset($release_group_choices[$release_group]))
                $updateset[] = "release_group = " . sqlesc($release_group);
                }
                //==End - now update the sets
                if (sizeof($updateset)>0) 
                @sql_query('UPDATE torrents SET ' . implode(',', $updateset) . ' WHERE id = ' . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
                write_log("torrent edited - " . htmlspecialchars($name) . ' was edited by ' . (($fetch_assoc['anonymous'] == 'yes') ? 'Anonymous' : htmlspecialchars($CURUSER['username'])) . "");
                $modfile = 'cache/details/'.$id.'_moddin.txt';
                if (file_exists($modfile))
                unlink($modfile);
                $returl = (isset($_POST['returnto']) ? '&returnto=' . urlencode($_POST['returnto']) : 'details.php?id=' . $id . '&edited=1');
                header("Refresh: 0; url=$returl");
?>