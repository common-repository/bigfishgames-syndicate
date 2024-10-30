<?php
/*
Plugin Name: Bigfishgames Syndicate
Plugin URI: http://www.bestbuygame.com/bigfishgames-syndicate.html
Description: Automatically add new games from <a href="http://www.bigfishgames.com/index.html?afcode=affbd5b092f2">Big Fish Games</a>. Go to Settings -> Bigfishgames for setup.
Version: 1.2
Author: Codemaster
Author URI: http://ktulhu.net/
*/
$bigfishgames_syndicate_version = '1.2'; // url-safe version string
$bigfishgames_syndicate_date = '2009-01-10'; // date this version was released, beats a version #

/*
Copyright 2008-2009 Codemaster (codemaster@ktulhu.net)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

add_action('init', 'bigfishgames_syndicate_init');
add_action('admin_menu', 'bigfishgames_syndicate_admin_menu');
add_action('admin_head', 'bigfishgames_syndicate_admin_head');

function bigfishgames_syndicate_init () {
	global $wpdb;

	$bigfishgames_syndicate_interval = get_option('bigfishgames_syndicate_interval');
	$bigfishgames_syndicate_wh_checked = get_option('bigfishgames_syndicate_wh_checked');
	$bigfishgames_syndicate_last_posts = get_option('bigfishgames_syndicate_last_posts');
	
	$bigfishgames_syndicate_interval = max (60, $bigfishgames_syndicate_interval);
	
	if (time () > $bigfishgames_syndicate_wh_checked + $bigfishgames_syndicate_interval) {
	
		update_option ('bigfishgames_syndicate_wh_checked', time ());
		
		$bigfishgames_syndicate_aff_code = get_option('bigfishgames_syndicate_aff_code');
		$bigfishgames_syndicate_template = get_option('bigfishgames_syndicate_template');
		$bigfishgames_syndicate_title_prefix = get_option('bigfishgames_syndicate_title_prefix');
		$bigfishgames_syndicate_title_suffix = get_option('bigfishgames_syndicate_title_suffix');
		
		$bigfishgames_syndicate_pc_en = get_option('bigfishgames_syndicate_pc_en');
		$bigfishgames_syndicate_pc_de = get_option('bigfishgames_syndicate_pc_de');
		$bigfishgames_syndicate_pc_fr = get_option('bigfishgames_syndicate_pc_fr');
		$bigfishgames_syndicate_pc_es = get_option('bigfishgames_syndicate_pc_es');
		$bigfishgames_syndicate_mac_en = get_option('bigfishgames_syndicate_mac_en');
		
		$bigfishgames_syndicate_allow_pings = get_option('bigfishgames_syndicate_allow_pings');
		$bigfishgames_syndicate_allow_comments = get_option('bigfishgames_syndicate_allow_comments');
		
		$urls = array ();
		
		if ($bigfishgames_syndicate_pc_en) {	$urls []= "http://www.gaamle.com/plugins/data/fresh.bfg.pc.en.dat"; }
		if ($bigfishgames_syndicate_pc_de) {	$urls []= "http://www.gaamle.com/plugins/data/fresh.bfg.pc.de.dat"; }
		if ($bigfishgames_syndicate_pc_fr) {	$urls []= "http://www.gaamle.com/plugins/data/fresh.bfg.pc.fr.dat"; }
		if ($bigfishgames_syndicate_pc_es) {	$urls []= "http://www.gaamle.com/plugins/data/fresh.bfg.pc.es.dat"; }
		if ($bigfishgames_syndicate_mac_en) {	$urls []= "http://www.gaamle.com/plugins/data/fresh.bfg.mac.en.dat"; }
		
		foreach ($urls as $u) {
			$c = @join ("", @file ($u));
			
			$info = @unserialize ($c);
			
			if (strlen ($info["title"]) > 0) {
			
				$posted = false;
				$q = "select post_title from " . $wpdb->posts . " order by id desc limit " . $bigfishgames_syndicate_last_posts;
				$posts = $wpdb->get_results ($q);
				foreach ($posts as $p) {
					if (stristr ( $p -> post_title, $info["title"])) {
						$posted = true;
					}
				}
				
				if (!$posted) {
			
					$date = null;
					$categories = null;
		
					// Meta
					$meta = array();
					
					$content = $bigfishgames_syndicate_template;
					
					$title = trim ($bigfishgames_syndicate_title_prefix . " " .  $info ["title"] . " " . $bigfishgames_syndicate_title_suffix);
					$title = preg_replace ("|\s+|", " ", $title);
					
					$content = str_replace ("{TITLE}", $title, $content);
					
					$content = str_replace ("{GENRES}", $info ["genres"], $content);
					$content = str_replace ("{DESC_SHORT}", $info ["desc_short"], $content);
					$content = str_replace ("{DESC_MED}", $info ["desc_med"], $content);
					$content = str_replace ("{DESC_LONG}", $info ["desc_long"], $content);
					$content = str_replace ("{SIZE}", $info ["size"], $content);
					$content = str_replace ("{SIZE_MB}", sprintf ("%.02f", $info ["size"] / (1024*1024)), $content);
					$content = str_replace ("{DOWNLOAD_URL}", $info ["download_url"], $content);
					$content = str_replace ("{BUY_URL}", $info ["buy_url"], $content);
					
					$content = str_replace ("{IMG_THUMB}", $info ["thumb"], $content);
					$content = str_replace ("{IMG_SMALL}", $info ["img_small"], $content);
					$content = str_replace ("{IMG_MED}", $info ["img_med"], $content);
					$content = str_replace ("{IMG_FEATURE}", $info ["img_feature"], $content);
					$content = str_replace ("{IMG_SUBFEATURE}", $info ["img_subfeature"], $content);
					
					$content = str_replace ("{IMG_SCREENSHOT_1}", $info ["screenshots"] [0], $content);
					$content = str_replace ("{IMG_SCREENSHOT_2}", $info ["screenshots"] [1], $content);
					
					$content = str_replace ("{AFF_CODE}", $bigfishgames_syndicate_aff_code, $content);
				
					// Create post
					$postid = bigfishgames_syndicate_insertPost(
						$wpdb->escape($title), 
						$wpdb->escape($content), 
						$date,
						$categories,
						'publish',
						null,
						$bigfishgames_syndicate_allow_pings,
						$bigfishgames_syndicate_allow_comments,
						$meta
					);
				}
			}
		}
	}
}

function bigfishgames_syndicate_insertPost($title, $content, $timestamp = null, $category = null, $status = 'draft', $authorid = null, $allowpings = true, $allowcomments = true, $meta = array())
  {
	$date = ($timestamp) ? gmdate('Y-m-d H:i:s', $timestamp) : null;
	$postid = wp_insert_post(array(
		'post_title' 	            => $title,
		'post_content'  	        => $content,
		'post_content_filtered'  	=> $content,
		'post_category'           => $category,
		'post_status' 	          => $status,
		'post_author'             => $authorid,
		'post_date'               => $date,
		'comment_status'          => $allowcomments,
		'ping_status'             => $allowpings
	));
		
		return $postid;
}


// Plugin config/data setup
if (function_exists('register_activation_hook')) {
	// for WP 2
	register_activation_hook(__FILE__, 'bigfishgames_syndicate_activation_hook');
}

function bigfishgames_syndicate_activation_hook() {
	return bigfishgames_syndicate_restore_config(False);
}

// restore built-in defaults, optionally overwriting existing values
function bigfishgames_syndicate_restore_config($force=False) {
	global $bigfishgames_syndicate_alllangset;
		
$template = "
<table>
	<tr>
		<td valign=\"top\">
			<img src=\"{IMG_FEATURE}\" />
		</td>
		<td>
			<p>{DESC_LONG}</p>
			<br/>
			<a href=\"{DOWNLOAD_URL}\">Download free trial ({SIZE_MB} Mb)</a>
			<br/>
			<a href=\"{BUY_URL}\">Buy full version</a>
		</td>
	</tr>
</table>
<!--more-->
<img src=\"{IMG_SCREENSHOT_1}\" />
<br/>
<img src=\"{IMG_SCREENSHOT_2}\" />
<br/>
<a href=\"{DOWNLOAD_URL}\">Download free trial ({SIZE_MB} Mb)</a>
<br/>
<a href=\"{BUY_URL}\">Buy full version</a>
";

	update_option ('bigfishgames_syndicate_aff_code', "affbd5b092f2");

	// tagline defaults to a Hitchiker's Guide to the Galaxy reference
	if ($force or !is_string (get_option ('bigfishgames_syndicate_template')))
		update_option ('bigfishgames_syndicate_template', $template);
		
	update_option ('bigfishgames_syndicate_title_prefix', "Free Download");
	update_option ('bigfishgames_syndicate_title_suffix', "Game");

	update_option ('bigfishgames_syndicate_pc_en', 1);
	update_option ('bigfishgames_syndicate_pc_de', 0);
	update_option ('bigfishgames_syndicate_pc_fr', 0);
	update_option ('bigfishgames_syndicate_pc_es', 0);
	update_option ('bigfishgames_syndicate_mac_en', 0);
	
	update_option ('bigfishgames_syndicate_allow_pings', 1);
	update_option ('bigfishgames_syndicate_allow_comments', 1);
	
	update_option ('bigfishgames_syndicate_interval', 3600);
	update_option ('bigfishgames_syndicate_last_posts', 100);
	update_option ('bigfishgames_syndicate_wh_checked', 0);
}

// Hook the admin_menu display to add admin page
function bigfishgames_syndicate_admin_menu() {
	add_submenu_page('options-general.php', 'Bigfishgames', 'Bigfishgames', 8, 'Bigfishgames', 'bigfishgames_syndicate_submenu');
}

// Admin page header
function bigfishgames_syndicate_admin_head() {
}

function bigfishgames_syndicate_message($message) {
	echo "<div id=\"message\" class=\"updated fade\"><p>$message</p></div>\n";
}

// The admin page
function bigfishgames_syndicate_submenu() {
	global $sociable_known_sites, $sociable_date, $sociable_files;

	// update options in db if requested
	if ($_REQUEST['restore']) {
		bigfishgames_syndicate_restore_config(True);
		bigfishgames_syndicate_message(__("Restored all settings to defaults.", 'bigfishgames_syndicate'));
		
	} else if ($_REQUEST['save']) {
	
		update_option ('bigfishgames_syndicate_aff_code', $_REQUEST ["bigfishgames_syndicate_aff_code"]);
	
		update_option ('bigfishgames_syndicate_template', stripslashes ($_REQUEST ["bigfishgames_syndicate_template"]));
		
		update_option ('bigfishgames_syndicate_title_prefix', stripslashes ($_REQUEST ["bigfishgames_syndicate_title_prefix"]));
		update_option ('bigfishgames_syndicate_title_suffix', stripslashes ($_REQUEST ["bigfishgames_syndicate_title_suffix"]));

		update_option ('bigfishgames_syndicate_pc_en', (int)$_REQUEST ["bigfishgames_syndicate_pc_en"]);
		update_option ('bigfishgames_syndicate_pc_de', (int)$_REQUEST ["bigfishgames_syndicate_pc_de"]);
		update_option ('bigfishgames_syndicate_pc_fr', (int)$_REQUEST ["bigfishgames_syndicate_pc_fr"]);
		update_option ('bigfishgames_syndicate_pc_es', (int)$_REQUEST ["bigfishgames_syndicate_pc_es"]);
		update_option ('bigfishgames_syndicate_mac_en', (int)$_REQUEST ["bigfishgames_syndicate_mac_en"]);
	
		update_option ('bigfishgames_syndicate_interval', (int)$_REQUEST ["bigfishgames_syndicate_interval"]);
		update_option ('bigfishgames_syndicate_last_posts', (int)$_REQUEST ["bigfishgames_syndicate_last_posts"]);
		
		update_option ('bigfishgames_syndicate_allow_pings', (int)$_REQUEST ["bigfishgames_syndicate_allow_pings"]);
		update_option ('bigfishgames_syndicate_allow_comments', (int)$_REQUEST ["bigfishgames_syndicate_allow_comments"]);
		
		update_option ('bigfishgames_syndicate_wh_checked', 0);
		
		bigfishgames_syndicate_message(__("Saved changes.", 'bigfishgames_syndicate'));
	}

	// load options from db to display
	$bigfishgames_syndicate_aff_code = get_option('bigfishgames_syndicate_aff_code');
	$bigfishgames_syndicate_template = get_option('bigfishgames_syndicate_template');
	$bigfishgames_syndicate_title_prefix = get_option('bigfishgames_syndicate_title_prefix');
	$bigfishgames_syndicate_title_suffix= get_option('bigfishgames_syndicate_title_suffix');
	$bigfishgames_syndicate_pc_en = get_option('bigfishgames_syndicate_pc_en');
	$bigfishgames_syndicate_pc_de = get_option('bigfishgames_syndicate_pc_de');
	$bigfishgames_syndicate_pc_fr = get_option('bigfishgames_syndicate_pc_fr');
	$bigfishgames_syndicate_pc_es = get_option('bigfishgames_syndicate_pc_es');
	$bigfishgames_syndicate_mac_en = get_option('bigfishgames_syndicate_mac_en');
	$bigfishgames_syndicate_interval = get_option('bigfishgames_syndicate_interval');
	$bigfishgames_syndicate_last_posts = get_option('bigfishgames_syndicate_last_posts');
	$bigfishgames_syndicate_allow_pings = get_option('bigfishgames_syndicate_allow_pings');
	$bigfishgames_syndicate_allow_comments = get_option('bigfishgames_syndicate_allow_comments');

	// display options
?>
<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">

<div class="wrap" id="bigfishgames_syndicate_options">

<h3><?php _e("Bigfishgames Syndicate", 'bigfishgames_syndicate'); ?></h3>

<div style="clear: left; display: none;"><br/></div>

<fieldset id="bigfishgames_syndicate_aff_code">
<p>
<?php _e("Affiliate Code", 'bigfishgames_syndicate'); ?>
 (Use your own or visit <a href="http://www.bigfishgames.com/index.html?afcode=affbd5b092f2">Big Fish Games</a> and join Affiliate Program. Code changes does not affect existing posts.):
</p>
<input type="text" name="bigfishgames_syndicate_aff_code" value="<?php echo htmlspecialchars($bigfishgames_syndicate_aff_code); ?>" />
</fieldset>

<fieldset id="bigfishgames_syndicate_lang">
<p><?php _e("Connect Feed(s):", 'bigfishgames_syndicate'); ?></p>
<input type="checkbox" value="1" name="bigfishgames_syndicate_pc_en"<?php echo ($bigfishgames_syndicate_pc_en) ? ' checked="checked"' : ''; ?> /> <?php _e("PC Games, English", 'bigfishgames_syndicate'); ?>
<br/>
<input type="checkbox" value="1" name="bigfishgames_syndicate_pc_de"<?php echo ($bigfishgames_syndicate_pc_de) ? ' checked="checked"' : ''; ?> /> <?php _e("PC Games, German", 'bigfishgames_syndicate'); ?>
<br/>
<input type="checkbox" value="1" name="bigfishgames_syndicate_pc_fr"<?php echo ($bigfishgames_syndicate_pc_fr) ? ' checked="checked"' : ''; ?> /> <?php _e("PC Games, French", 'bigfishgames_syndicate'); ?>
<br/>
<input type="checkbox" value="1" name="bigfishgames_syndicate_pc_es"<?php echo ($bigfishgames_syndicate_pc_es) ? ' checked="checked"' : ''; ?> /> <?php _e("PC Games, Spanish", 'bigfishgames_syndicate'); ?>
<br/>
<input type="checkbox" value="1" name="bigfishgames_syndicate_mac_en"<?php echo ($bigfishgames_syndicate_mac_en) ? ' checked="checked"' : ''; ?> /> <?php _e("Mac Games, English", 'bigfishgames_syndicate'); ?>
</fieldset>

<fieldset id="bigfishgames_syndicate_title_prefix">
<p>
<?php _e("Title Prefix:", 'bigfishgames_syndicate'); ?>
</p>
<input type="text" name="bigfishgames_syndicate_title_prefix" value="<?php echo htmlspecialchars($bigfishgames_syndicate_title_prefix); ?>" />
</fieldset>

<fieldset id="bigfishgames_syndicate_title_suffix">
<p>
<?php _e("Title Suffix:", 'bigfishgames_syndicate'); ?>
</p>
<input type="text" name="bigfishgames_syndicate_title_suffix" value="<?php echo htmlspecialchars($bigfishgames_syndicate_title_suffix); ?>" />
</fieldset>

<fieldset id="bigfishgames_syndicate_template">
<p><?php _e("Template:", 'bigfishgames_syndicate'); ?></p>
<textarea name="bigfishgames_syndicate_template" cols="50" rows="5"><?php echo htmlspecialchars($bigfishgames_syndicate_template); ?></textarea>

<p>Available tags:
<br/>
{TITLE}
{GENRES}
{DESC_SHORT}
{DESC_MED}
{DESC_LONG}
{SIZE}
{SIZE_MB}
{DOWNLOAD_URL}
{BUY_URL}
<br/>
{IMG_THUMB}
{IMG_SMALL}
{IMG_MED}
{IMG_FEATURE}
{IMG_SUBFEATURE}
				
{IMG_SCREENSHOT_1}
{IMG_SCREENSHOT_2}
</p>

</fieldset>

<fieldset id="bigfishgames_syndicate_interval">
<p>
<?php _e("Check updates interval (sec):", 'bigfishgames_syndicate'); ?>
</p>
<input type="text" name="bigfishgames_syndicate_interval" value="<?php echo htmlspecialchars($bigfishgames_syndicate_interval); ?>" />
</fieldset>

<fieldset id="bigfishgames_syndicate_last_posts">
<p>
<?php _e("Check last posts for already existing titles:", 'bigfishgames_syndicate'); ?>
</p>
<input type="text" name="bigfishgames_syndicate_last_posts" value="<?php echo htmlspecialchars($bigfishgames_syndicate_last_posts); ?>" />
</fieldset>




<fieldset id="bigfishgames_syndicate_lang">
<p><?php _e("Allow:", 'bigfishgames_syndicate'); ?></p>
<input type="checkbox" value="1" name="bigfishgames_syndicate_allow_pings"<?php echo ($bigfishgames_syndicate_allow_pings) ? ' checked="checked"' : ''; ?> /> <?php _e("Pings", 'bigfishgames_syndicate'); ?>
<br/>
<input type="checkbox" value="1" name="bigfishgames_syndicate_allow_comments"<?php echo ($bigfishgames_syndicate_allow_comments) ? ' checked="checked"' : ''; ?> /> <?php _e("Comments", 'bigfishgames_syndicate'); ?>
</fieldset>

<p class="submit"><input name="save" id="save" tabindex="3" value="<?php _e("Save Changes", 'bigfishgames_syndicate'); ?>" type="submit" /></p>
<p class="submit"><input name="restore" id="restore" tabindex="3" value="<?php _e("Restore Built-in Defaults", 'bigfishgames_syndicate'); ?>" type="submit" style="border: 2px solid #e00;" /></p>

</div>

<div class="wrap">
<p>
<?php _e('<a href="http://www.bestbuygame.com/bigfishgames-syndicate.html">Bigfishgames Syndicate</a> is copyright 2008-2009 by <a href="http://ktulhu.net/">Codemaster</a>, released under the GNU GPL version 2 or later. If you like Bigfishgames Syndicate, please send a link my way so other folks can find out about it.', 'bigfishgames_syndicate'); ?>
</p>
</div>

</form>

<?php
}
?>