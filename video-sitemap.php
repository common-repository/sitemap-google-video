<?php
/*
Plugin Name: Google Sitemap video 
Plugin URI: 
Description: Sitemap Google video, indexation de videos dailymotion.
Author: Damien Capek
Version: 0.0.1
Author URI: http://www.waouo.com/
*/

/*  Copyright 2011  Damien Capek  (email : damien@capek.fr)
 
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
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/ 

add_action ('admin_menu', 'video_sitemap_generate_page');

function video_sitemap_generate_page () {
	if (function_exists ('add_submenu_page'))
    	add_submenu_page ('tools.php', __('Video Sitemap'), __('Video Sitemap'),
        	'manage_options', 'video-sitemap-generate-page', 'video_sitemap_generate');
}

	/**
	 * Checks if a file is writable and tries to make it if not.
	 *
	 * @since 3.05b
	 * @access private
	 * @author  VJTD3 <http://www.VJTD3.com>
	 * @return bool true if writable
	 */
	function IsVideoSitemapWritable($filename) {
		//can we write?
		if(!is_writable($filename)) {
			//no we can't.
			if(!@chmod($filename, 0666)) {
				$pathtofilename = dirname($filename);
				//Lets check if parent directory is writable.
				if(!is_writable($pathtofilename)) {
					//it's not writeable too.
					if(!@chmod($pathtoffilename, 0666)) {
						//darn couldn't fix up parrent directory this hosting is foobar.
						//Lets error because of the permissions problems.
						return false;
					}
				}
			}
		}
		//we can write, return 1/true/happy dance.
		return true;
	}


function video_sitemap_generate () {

	if ($_POST ['submit']) {
		$st = video_sitemap_loop ();
		if (!$st) {
echo '';	
exit();
}

?>

<div class="wrap">
<div style="float:right; margin:10px">
</div>
<h2>Google Sitemap video</h2>
<?php $sitemapurl = get_bloginfo('home') . "/video-sitemap.xml"; ?>
<p>Le Sitemap video a été généré avec succès. ouvrez le <a target="_blank" href="<?php echo $sitemapurl; ?>">Sitemap XML</a></p>
<p>Vous pouvez soumettre votre Sitemap vidéo à <a href="http://www.google.com/webmasters/tools/" target="_blank">Webmaster Tools</a> ou directement avec<a target="_blank" href="http://www.google.com/webmasters/sitemaps/ping?sitemap=<?php echo $sitemapurl; ?>">ping Google</a>.</p>
<p></p>
<?php } else { ?>
<div class="wrap">
  <h2>Google Sitemap video par <a href="http://www.waouo.com/">Damien Capek</a></h2>
  <p>Ce plugin vous permet d'indexer les videos dailymotion sur google videos</p>
  <h3>Creer un sitemap videos</h3>
  <form id="options_form" method="post" action="">
    <input type="checkbox" id="sboption" name="time" value="1" />
    <label for="sboption">Inclure la duree de la video? (Je ne le conseille pas pour les sites avec beaoucoup de videos)</label>
    <div class="submit">
      <input type="submit" name="submit" id="sb_submit" value="Generate Video Sitemap" />
    </div>
  </form>
  <p></p>
  <p></p>
  <p></p>
</div>
<?php	}
}

function video_sitemap_loop () {
	global $wpdb;

	$posts = $wpdb->get_results ("SELECT id, post_title, post_content, post_date_gmt, post_excerpt 
							FROM $wpdb->posts 
							WHERE post_status = 'publish' 
							AND (post_type = 'post' OR post_type = 'page')
							AND post_content LIKE '%dailymotion.com%' 
							ORDER BY post_date DESC");

	if (empty ($posts)) {
		return false;

	} else {

		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";       		     	
	    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . "\n";	
		
                       foreach ($posts as $post) {
			if (preg_match_all ("(<embed[^>]*src=['\"]http:\/\/www.dailymotion.com\/swf\/video\/([a-z0-9\-]*)|dailymotion.com\/video\/([a-z0-9\-]*))", 
				$post->post_content, $matches, PREG_SET_ORDER)) {
					$excerpt = ($post->post_title != "") ? $post->post_title : $post->post_title ; 
					$permalink = get_permalink($post->id); 
					
                                        foreach ($matches as $match) {
						$id = $match [1];
						if ($id == '') $id = $match [2];
						if ($id == '') $id = $match [3];
	
					$xml .= "\n <url>\n";
					$xml .= " <loc>$permalink</loc>\n";
					$xml .= " <video:video>\n";
					$xml .= "  <video:player_loc allow_embed=\"yes\" autoplay=\"autoplay=1\">http://www.dailymotion.com/swf/$id</video:player_loc>\n";
					$xml .= "  <video:title>" . htmlspecialchars($post->post_title) . "</video:title>\n";
					$xml .= "  <video:description>Divertissement, Buzz, Photo et Video Sur waouo : " . htmlspecialchars($excerpt) . ". Le meilleur du divertissement</video:description>\n";		
                                        $xml .= "  <video:thumbnail_loc>http://www.dailymotion.com/thumbnail/320x240/video/$id</video:thumbnail_loc>\n";

					$xml .= " </video:video>\n </url>";
				}
			}
		}

		$xml .= "\n</urlset>";
	}

	$video_sitemap_url = $_SERVER["DOCUMENT_ROOT"] . '/video-sitemap.xml';
	if (IsVideoSitemapWritable($_SERVER["DOCUMENT_ROOT"]) || IsVideoSitemapWritable($video_sitemap_url)) {
		if (file_put_contents ($video_sitemap_url, $xml)) {
			return true;
		}
	} 
echo '<br /><div class="wrap"><h2>Erreur d\'écriture du fichier</h2><p>Le sitemap a été généré avec succès mais le plugin n\'a pas pu enregistrer le fichier xml dans votre dossier racine de WordPress <strong>' . $_SERVER["DOCUMENT_ROOT"] . '</strong> probablement parce que le dossier est protéger en écriture <a href="http://codex.wordpress.org/Changing_File_Permissions" target="_blank">les permissions d\'écriture</a>.</p><p>Vous pouvez manuellement copier-coller le texte suivant dans un fichier et enregistrez vidéo-sitemap.xml dans votre dossier racine de WordPress.
 </p><br /><textarea rows="30" cols="150" style="font-family:verdana; font-size:11px;color:#666;background-color:#f9f9f9;padding:5px;margin:5px">' . $xml . '</textarea></div>';	
	exit();
}

?>
