<?php
/**
 *   https://09source.kicks-ass.net:8443/svn/installer09/
 *   Licence Info: GPL
 *   Copyright (C) 2010 Installer09 v.1
 *   A bittorrent tracker source based on TBDev.net/tbsource/bytemonsoon.
 *   Project Leaders: Mindless,putyn,kidvision.
 **/
/**********************************************************
New 2010 forums that don't suck for TB based sites....

Beta Thurs Sept 9th 2010 v0.5

Powered by Bunnies!!!
***************************************************************/

if (!defined('BUNNY_FORUMS')) 
{
	$HTMLOUT .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
        <head>
        <meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
        <title>ERROR</title>
        </head><body>
        <h1 style="text-align:center;">ERROR</h1>
        <p style="text-align:center;">How did you get here? silly rabbit Trix are for kids!.</p>
        </body></html>';
	print $HTMLOUT;
	exit();
}

$ASC_DESC =  ((isset($_GET['ASC_DESC']) && $_GET['ASC_DESC'] === 'ASC' ) ? 'ASC '  :  'DESC ' );

$res_count = sql_query('SELECT COUNT(p.id) FROM posts AS p 
								LEFT JOIN topics AS t ON p.topic_id = t.id 
								LEFT JOIN forums AS f ON f.id = t.forum_id 
								WHERE '.($CURUSER['class'] < UC_MODERATOR ? 'p.status = \'ok\' AND t.status = \'ok\' AND' : 
								($CURUSER['class'] < $min_delete_view_class ? 'p.status != \'deleted\' AND t.status != \'deleted\'  AND' : '')).'
								p.user_id = '.$CURUSER['id'].' AND f.min_class_read <= '.$CURUSER['class']);
$arr_count = mysql_fetch_row($res_count);
$count = $arr_count[0];

	  //=== get stuff for the pager
	$page = isset($_GET['page']) ? (int)$_GET['page'] : 0;
	$perpage = isset($_GET['perpage']) ? (int)$_GET['perpage'] : 20;
	
	$subscription_on_off = (isset($_GET['s'])  ? ($_GET['s'] == 1 ? '<br /><div style="font-weight: bold;">Subscribed to topic <img src="pic/forums/subscribe.gif" alt=" " width="25"></div>' : '<br /><div style="font-weight: bold;">Unsubscribed from topic <img src="pic/forums/unsubscribe.gif" alt=" " width="25"></div>') : '');
    
list($menu, $LIMIT) = pager_new($count, $perpage, $page, 'forums.php?action=view_my_posts'.(isset($_GET['perpage']) ? '&amp;perpage='.$perpage : ''));  

$res = sql_query('SELECT p.id AS post_id, p.topic_id, p.user_id, p.added, p.body, p.edited_by, p.edit_date, p.icon, p.post_title, p.bbcode, p.post_history, p.edit_reason, p.ip, p.status AS post_status,
t.id AS topic_id, t.topic_name, t.forum_id, t.sticky, t.locked, t.poll_id, t.status AS topic_status,
f.name AS forum_name, f.description
FROM posts AS p 
LEFT JOIN topics AS t ON p.topic_id = t.id 
LEFT JOIN forums AS f ON f.id = t.forum_id 
WHERE  '.($CURUSER['class'] < UC_MODERATOR ? 'p.status = \'ok\' AND t.status = \'ok\' AND' : 
($CURUSER['class'] < $min_delete_view_class ? 'p.status != \'deleted\' AND t.status != \'deleted\'  AND' : '')).'
 p.user_id = '.$CURUSER['id'].' AND f.min_class_read <= '.$CURUSER['class'].'
ORDER BY p.id '.$ASC_DESC.$LIMIT);


	$links = '<span style="text-align: center;"><a class="altlink" href="forums.php">Main Fourms</a> |  '.$mini_menu.'<br /><br /></span>';
	$the_top_and_bottom =  '<tr><td class="three" colspan="3" align="center">'.(($count > $perpage) ? $menu : '').'</td></tr>';

	$HTMLOUT .= '<h1>'.$count.' Posts by '.print_user_stuff($CURUSER).'</h1>'.$links.'
			<div><a class="altlink" href="forums.php?action=view_my_posts" title="view posts from newest to oldest">Sort by newest posts first</a> || 
			<a class="altlink" href="forums.php?action=view_my_posts&amp;ASC_DESC=ASC" title="view posts from oldest to newest">Sort by oldest posts first</a></div><br />';
	
	$HTMLOUT .= '<a name="top"></a><table border="0" cellspacing="5" cellpadding="10" width="90%">'.$the_top_and_bottom;
		$colour='';
		$edited_by = '';
		//=== lets start the loop \o/
		while ($arr = mysql_fetch_assoc($res))
		{
		//=== change colors
		$colour = (++$colour)%2;
		$class = ($colour == 0 ? 'one' : 'two');
		$class_alt = ($colour == 0 ? 'two' : 'one');
		
		//=== topic status
		$topic_status = $arr['topic_status'];
	
		switch ($topic_status)
		{
		case 'ok':
		$topic_status_image = '';
		break;
		case 'recycled':
		$topic_status_image = '<img src="pic/forums/recycle_bin.gif" alt="Recycled" title="this thread is currently in the recycle-bin" />';
		break;
		case 'deleted':
		$topic_status_image = '<img src="pic/forums/delete_icon.gif" alt="Deleted" title="this thread is currently deleted" />';
		break;		
		}
		
		//=== post status
		$post_status = $arr['post_status'];
	
		switch ($post_status)
		{
		case 'ok':
		$post_status = $class;
		$post_status_image = '';
		break;
		case 'recycled':
		$post_status = 'recycled';
		$post_status_image = ' <img src="pic/forums/recycle_bin.gif" alt="Recycled" title="this post is currently in the recycle-bin" />';
		break;
		case 'deleted':
		$post_status = 'deleted';
		$post_status_image = ' <img src="pic/forums/delete_icon.gif" alt="Deleted" title="this post is currently deleted" />';
		break;		
		}

		$post_icon = ($arr['icon'] !== '' ? '<img src="pic/smilies/'.htmlspecialchars($arr['icon']).'.gif" alt="icon" /> ' : '<img src="pic/forums/topic_normal.gif" alt="icon" /> ');
		$post_title = ($arr['post_title'] !== '' ? ' <span style="font-weight: bold; font-size: x-small;">'.htmlentities($arr['post_title'], ENT_QUOTES).'</span>' : 'Link to Post');
		
		if ($arr['edit_date'] > 0)
		{
		$res_edited = sql_query('SELECT username FROM users WHERE id='.$arr['edited_by']);
		$arr_edited = mysql_fetch_assoc($res_edited);
		
		$edited_by = '<br /><br /><br /><span style="font-weight: bold; font-size: x-small;">Last edited by <a class="altlink" href="member_details.php?id='.$arr['edited_by'].'">'.$arr_edited['username'].'</a>
				 at '.get_date($arr['edit_date'],'').' GMT '.($arr['edit_reason'] !== '' ? ' </span>[ Reason: '.htmlspecialchars($arr['edit_reason']).' ] <span style="font-weight: bold; font-size: x-small;">' : '').'
				 '.(($CURUSER['class'] >= UC_MODERATOR && $arr['post_history'] !== '') ? 
				 ' <a class="altlink" href="forums.php?action=view_post_history&amp;post_id='.$arr['post_id'].'&amp;forum_id='.$arr['forum_id'].'&amp;topic_id='.$arr['topic_id'].'">read post history</a></span><br />' : '');
		}
		
		$body = ($arr['bbcode'] == 'yes' ? format_comment($arr['body']) : format_comment_no_bbcode($arr['body']));
		$post_id = $arr['post_id'];
		$HTMLOUT .='<tr>
		<td class="forum_head_dark" colspan="3" align="left">Forum:  
		<span style="color: white;font-weight: bold;">
		<a class="altlink" href="forums.php?action=view_forum&amp;forum_id='.$arr['forum_id'].'" title="Link to Forum">
		'.htmlentities($arr['forum_name'], ENT_QUOTES).'</a></span>&nbsp;&nbsp;&nbsp;&nbsp;
		<span style="color: white;font-weight: bold;">
		Topic: <a class="altlink" href="forums.php?action=view_topic&amp;topic_id='.$arr['topic_id'].'" title="Link to Forum">
		'.htmlentities($arr['topic_name'], ENT_QUOTES).'</a>'.$topic_status_image.'</span>
		</td>
		</tr>
		<tr>
		<td class="forum_head" align="left" width="100" valign="middle"><a name="'.$post_id.'"></a></td>
		<td class="forum_head" align="left" valign="middle">
		<span style="white-space:nowrap;">'.$post_icon.'
		<a class="altlink" href="forums.php?action=view_topic&amp;topic_id='.$arr['topic_id'].'&amp;page='.$page.'#'.$arr['post_id'].'" title="Link to Post">
		'.$post_title.'</a>&nbsp;&nbsp;'.$post_status_image.'
		&nbsp;&nbsp; posted on: '.get_date($arr['added'],'').' ['.get_date($arr['added'],'',0,1).']</span></td>
		<td class="forum_head" align="right" valign="middle">
		<span style="white-space:nowrap;"> 
		<a href="forums.php?action=view_my_posts&amp;page='.$page.'#top"><img src="pic/forums/up.gif" alt="top" title="top" /></a> 
		<a href="forums.php?action=view_my_posts&amp;page='.$page.'#bottom"><img src="pic/forums/down.gif" alt="bottom" title="bottom" /></a> 
		</span></td>
		</tr>	
		<tr>
		<td class="'.$class_alt.'" align="center" width="100px" valign="top">'.avatar_stuff($CURUSER ,120).'<br />
		'.print_user_stuff($CURUSER).($CURUSER['title'] == '' ? '' : '<br /><span style=" font-size: xx-small;">['.htmlspecialchars($CURUSER['title']).']</span>').'<br />
		<span style="font-weight: bold;">'.get_user_class_name($CURUSER['class']).'</span><br />
		</td>
		<td class="'.$post_status.'" align="left" valign="top" colspan="2">'.$body.$edited_by.'</td></tr>
		<tr><td class="'.$class_alt.'" align="right" valign="middle" colspan="3"></td></tr>';
		} //=== end while loop 

				
		$HTMLOUT .= $the_top_and_bottom.'</table><a name="bottom"></a><br />'.$links.'<br />';
?>