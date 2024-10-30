<?php
	/*
		Plugin Name: IsSiY (Include second Sitemap into Yoast) Wordpress SEO Extension
		Description: Adds "More Sitemaps" to your SEO Settings. If you are using a second Wordpress-Installation in a subdirectory, you can now easily add the second Sitemap into the main one.
		Author: Eric Marten
		Version: 1.1
		Plugin URI: http://www.seo-research.de/wordpress/plugins/more-sitemaps/
		Author URI: http://www.seo-research.de/
		License: GPL v3

		WordPress IsSiY Plugin
		Copyright (C) 2014, Eric Marten, w0xz.eric@gmail.com

		This program is free software: you can redistribute it and/or modify
		it under the terms of the GNU General Public License as published by
		the Free Software Foundation, either version 3 of the License, or
		(at your option) any later version.

		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License
		along with this program.  If not, see <http://www.gnu.org/licenses/>.
	*/
	
	$more_sitemaps=get_option("ys_more_sitemaps","");
	
	
		
	add_action('admin_menu', 'register_my_custom_submenu_page');

	function register_my_custom_submenu_page() {
		add_submenu_page( 'wpseo_dashboard', __('More')." ".__("Sitemap")."s", __('More')." ".__("Sitemap")."s", 'manage_options', 'ysms_smi_page', 'ysms_smi_menu' ); 
	}

	function ysms_smi_menu()
	{
		global $more_sitemaps;
		if (isset($_POST["more_sitemaps"]))
		{
			update_option("ys_more_sitemaps",$_POST["more_sitemaps"]);
			$more_sitemaps=get_option("ys_more_sitemaps","");
		}
		echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
			echo '<h2>'.__('More')." ".__("Sitemap")."s".'</h2>';
			echo __("Please type one Sitemap-URL per row");
			echo ". <small>(".__("with http")."://) ". __("and so on..")."</small>";
			?>
			<form action="" method="POST">
			<textarea style="width:100%; height:200px;" name="more_sitemaps"><?php echo $more_sitemaps; ?></textarea>
			<br />
			<input type="submit" value="<?php echo __("Update"); ?>">
			</form>
			<?php
		echo '</div>';
	}


	function ysms_smi_curl_download($Url)
	{
		if (!function_exists('curl_init')){
			die('Sorry cURL is not installed!');
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $Url);
		curl_setopt($ch, CURLOPT_REFERER, site_url());
		curl_setopt($ch, CURLOPT_USERAGENT, "Sitemap-Includer ".site_url());
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 2);
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}
	
	$temp=explode("/",$_SERVER["REQUEST_URI"]);
	$temp=array_pop($temp);
	$temp=explode("?",$temp);
	$temp=$temp[0];
	if ($temp=="sitemap_index.xml")
	{
		ob_start("yoast_seo_more_sitemaps");
	}
	
	function yoast_seo_more_sitemaps($string)
	{
		global $more_sitemaps;
		$more_sitemaps=str_replace("\r","",$more_sitemaps);
		$more_sitemaps=str_replace("\t","",$more_sitemaps);
		
		$string=str_replace("</sitemapindex>","[MORE_SITEMAPS]\n</sitemapindex>",$string);
		$add="";
		
		$more_sitemaps=explode("\n",$more_sitemaps);
		foreach ($more_sitemaps as $more)
		{
			$more=trim($more);
			if (!empty($more))
			{
				$lastmod=0;
				$lastmod1=ysms_smi_curl_download($more);
				$lastmod1=explode("<lastmod>",$lastmod1);
				if (!isset($lastmod1[1])) { $lastmod1=0; }
				else
				{
					array_unshift($lastmod1);
					foreach ($lastmod1 as $last)
					{
						$last=explode("</l",$last);
						$last=$last[0];
						$last=strtotime($last);
						if ($last>$lastmod) { $lastmod=$last; }
					}
				}
				$add.="<sitemap>\n<loc>".$more."</loc>\n<lastmod>".date("Y-m-d",$lastmod)."T".date("H:i:s",$lastmod)."+00:00</lastmod>\n</sitemap>\n";
			}
			
		}
		$string=str_replace("[MORE_SITEMAPS]",$add,$string);
		return $string;
	}