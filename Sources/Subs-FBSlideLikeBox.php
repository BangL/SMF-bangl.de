<?php
/**
 * @package Facebook Slide Like Box
 * @author phantom
 * @copyright 2012
 * @license http://creativecommons.org/licenses/by-nc-nd/3.0/ CC BY-NC-ND 3.0
 * @version 2.1.3
**/

if (!defined('SMF'))
	die('Hacking attempt...');
	
define("FBSlideLikeBox_ver", "2.1.3");

// Create new area in  mod settings
function FBSlideLikeBox_admin_areas(&$areas)
{
	global $txt;

	loadLanguage('FBSlideLikeBox');

	$areas['config']['areas']['modsettings']['subsections']['FBSlideBox'] = array($txt['fb_slide_box_admin']);
}

// add new subaction with settings
function FBSlideLikeBox_modify_modifications(&$sub_actions)
{
	$sub_actions['FBSlideBox'] = 'FBSlideLikeBox_settings';
}

// load styles, JS and everything else...
function FBSlideLikeBox_load_theme()
{
	// globalize everything that we may need
	global $modSettings, $context, $settings, $sourcedir, $txt, $options;

	// we need here one txt string so let's load it
	loadLanguage('FBSlideLikeBox');

	// if setting is empty load defautls...	
	$modSettings['fb_slide_box_width'] = !empty($modSettings['fb_slide_box_width']) ? $modSettings['fb_slide_box_width'] : 292;
	$modSettings['fb_slide_box_height'] = !empty($modSettings['fb_slide_box_height']) ? $modSettings['fb_slide_box_height'] : 590;
	$modSettings['fb_slide_box_distance'] = !empty($modSettings['fb_slide_box_distance']) ? $modSettings['fb_slide_box_distance'] : 20;
	//yes, this one is by default empty on purpose, without it there will be error in logs
	$modSettings['fb_slide_box_appId'] = !empty($modSettings['fb_slide_box_appId']) ? $modSettings['fb_slide_box_appId'] : '';
	$modSettings['fb_slide_box_backgroundc'] = !empty($modSettings['fb_slide_box_backgroundc']) ? $modSettings['fb_slide_box_backgroundc'] : '#f8f8f8';
	$modSettings['fb_slide_box_borderc'] = !empty($modSettings['fb_slide_box_borderc']) ? $modSettings['fb_slide_box_borderc'] : '#3B5998';
	$modSettings['fb_slide_box_image_name'] = !empty($modSettings['fb_slide_box_image_name']) ? $modSettings['fb_slide_box_image_name'] : 'default.png';
	$modSettings['fb_slide_box_mode'] = !empty($modSettings['fb_slide_box_mode']) ? $modSettings['fb_slide_box_mode'] : 'html5';
	
	$FBiconImage = $settings['default_theme_dir'].'/images/FBSlideLikeBox/'.$modSettings['fb_slide_box_image_name'];
	$FBiconImageURL = $settings['default_theme_url'].'/images/FBSlideLikeBox/'.$modSettings['fb_slide_box_image_name'];

	// check size of icon image
	$size = getimagesize($FBiconImage);

	/*
	* If Mobile Device Detect is installed it uses it to hide slide bof when user is using mobile device.
	* If it is not installed then mod uses built in function to check if user is browsing forum from phone.
	* You can download Mobile Device Detect from http://custom.simplemachines.org/mods/index.php?mod=3349
	*/

	// if detected mobile device disable slide box
	if (!empty($modSettings['fb_slide_box_mobile']))
	{
		if(file_exists($sourcedir .'/Subs-MobileDetect.php'))
		{
			// for those who have latest version of Mobile Device Detect...
			if(function_exists('CheckIsMobile'))
			{
				if (CheckIsMobile()) return;
			}

			// ... and for those who did not updated it
			if(function_exists('isMobile'))
			{
				if ($context['device']->isMobile())	return;
			}
		}
		else
		{
			//detectBrowser();
			if ($context['browser']['is_iphone'] || $context['browser']['is_android']) return;
		}
	}

	//Disable mod in few actiopns by default
	if (in_array($context['current_action'], array('admin', 'moderate', 'helpadmin', 'printpage', 'kitsitemap')) || WIRELESS) return;

	if (isset($_REQUEST['xml']) || !empty($options['fb_slide_box_user'])) return;

	if (!empty($modSettings['fb_slide_box_actions']) && in_array($context['current_action'], array(''.$modSettings['fb_slide_box_actions'].''))) return;	

	if (allowedto('FBSlideLikeBox_display') && !empty($modSettings['fb_slide_box_enable']) && !empty($modSettings['fb_slide_box_url']))
	{
		$context['html_headers'] .= '
			<style type="text/css">
				#FBSlideLikeBox_left {background: url('.$FBiconImageURL.') '.$modSettings['fb_slide_box_width'].'px 0 no-repeat; float: left; height: '.$size[1].'px; position: fixed; left: -'.$modSettings['fb_slide_box_width'].'px; padding-right: '.$size[0].'px; top: '.$modSettings['fb_slide_box_distance'].$modSettings['fb_slide_box_distance2'].'; width: '.$modSettings['fb_slide_box_width'].'px; z-index: 2000;}
				#FBSlideLikeBox_left #FBSlideLikeBox3_left {height: '.$modSettings['fb_slide_box_height'].'px; right: 0; position: absolute; border: 3px solid '.$modSettings['fb_slide_box_borderc'].'; width: '.$modSettings['fb_slide_box_width'].'px; background: '.$modSettings['fb_slide_box_backgroundc'].';}
				#FBSlideLikeBox_right {background: url('.$FBiconImageURL.') 0 0 no-repeat; float: right; height: '.$size[1].'px; position: fixed; right: -'.$modSettings['fb_slide_box_width'].'px; padding-left: '.$size[0].'px; top: '.$modSettings['fb_slide_box_distance'].$modSettings['fb_slide_box_distance2'].'; width: '.$modSettings['fb_slide_box_width'].'px; z-index: 2000;}
				#FBSlideLikeBox_right #FBSlideLikeBox3_right {height: '.$modSettings['fb_slide_box_height'].'px; left: 0; position: absolute; border: 3px solid '.$modSettings['fb_slide_box_borderc'].'; width: '.$modSettings['fb_slide_box_width'].'px; background: '.$modSettings['fb_slide_box_backgroundc'].';}
				#FBSlideLikeBox_'.$modSettings['fb_slide_box_position'].' #FBSlideLikeBox2_'.$modSettings['fb_slide_box_position'].' {position: relative; clear: both; width: auto;}
			</style>';

		//allow admins disable nice animations... :P
		if (empty($modSettings['fb_slide_box_disable_animation']))
		{
			$context['html_headers'] .= '
				<script type="text/javascript">!window.jQuery && document.write(unescape(\'%3Cscript src="http://code.jquery.com/jquery.min.js"%3E%3C/script%3E\'))</script>
				<script type="text/javascript">
				 jQuery.noConflict();
					jQuery(document).ready(function($)
						{
							$("#FBSlideLikeBox_'.$modSettings['fb_slide_box_position'].'").'.$modSettings['fb_slide_box_show_after'].'(function()
						{
							$(this).stop().animate({'.$modSettings['fb_slide_box_position'].': 0}, "'.$modSettings['fb_slide_box_open_animation'].'");
						}).mouseleave(function()
						{
							$(this).stop().animate({'.$modSettings['fb_slide_box_position'].': -'.$modSettings['fb_slide_box_width'].'}, "'.$modSettings['fb_slide_box_close_animation'].'");
						});;
						});
				</script>';
		}
		else
		{
			$context['html_headers'] .= '
				<style type="text/css">
					#FBSlideLikeBox_'.$modSettings['fb_slide_box_position'].':hover{'.$modSettings['fb_slide_box_position'].':0px;}
				</style>';
		}
		
		if($modSettings['fb_slide_box_mode'] == "html5")
		{
			if($modSettings['fb_slide_box_show_after'] == "mouseenter")
			{
			$context['insert_after_template'] .= '
			<!--Facebook Slide Like Box '.FBSlideLikeBox_ver.' HTML5-->
				<div id="FBSlideLikeBox_'.$modSettings['fb_slide_box_position'].'" title="'.$txt['fb_slide_box_find'].'"  onclick="location.href=\''.$modSettings['fb_slide_box_url'].'\';" style="cursor:pointer;">
					<div id="FBSlideLikeBox2_'.$modSettings['fb_slide_box_position'].'">
						<div id="FBSlideLikeBox3_'.$modSettings['fb_slide_box_position'].'">
							<div id="fb-root"></div>
							<script>(function(d, s, id) {
							  var js, fjs = d.getElementsByTagName(s)[0];
							  if (d.getElementById(id)) return;
							  js = d.createElement(s); js.id = id;
							  js.src = "//connect.facebook.net/'.$modSettings['fb_slide_box_locale'].'/all.js#xfbml=1&appId='.$modSettings['fb_slide_box_appId'].'";
							  fjs.parentNode.insertBefore(js, fjs);
							}(document, \'script\', \'facebook-jssdk\'));</script>

							<div class="fb-like-box" data-href="'.$modSettings['fb_slide_box_url'].'" data-width="'.$modSettings['fb_slide_box_width'].'" data-height="'.$modSettings['fb_slide_box_height'].'" data-colorscheme="'.$modSettings['fb_slide_box_colorscheme'].'" data-show-faces="'.$modSettings['fb_slide_box_show_faces'].'" data-border-color="'.$modSettings['fb_slide_box_borderc'].'" data-stream="'.$modSettings['fb_slide_box_show_stream'].'" data-header="'.$modSettings['fb_slide_box_show_header'].'"></div>
						</div>
					</div>
				</div>';
			}
			else
			{
			$context['insert_after_template'] .= '
			<!--Facebook Slide Like Box '.FBSlideLikeBox_ver.' HTML5-->
				<div id="FBSlideLikeBox_'.$modSettings['fb_slide_box_position'].'" title="'.$txt['fb_slide_box_find'].'">
					<div id="FBSlideLikeBox2_'.$modSettings['fb_slide_box_position'].'">
						<div id="FBSlideLikeBox3_'.$modSettings['fb_slide_box_position'].'">
							<div id="fb-root"></div>
							<script>(function(d, s, id) {
							  var js, fjs = d.getElementsByTagName(s)[0];
							  if (d.getElementById(id)) return;
							  js = d.createElement(s); js.id = id;
							  js.src = "//connect.facebook.net/'.$modSettings['fb_slide_box_locale'].'/all.js#xfbml=1&appId='.$modSettings['fb_slide_box_appId'].'";
							  fjs.parentNode.insertBefore(js, fjs);
							}(document, \'script\', \'facebook-jssdk\'));</script>

							<div class="fb-like-box" data-href="'.$modSettings['fb_slide_box_url'].'" data-width="'.$modSettings['fb_slide_box_width'].'" data-height="'.$modSettings['fb_slide_box_height'].'" data-colorscheme="'.$modSettings['fb_slide_box_colorscheme'].'" data-show-faces="'.$modSettings['fb_slide_box_show_faces'].'" data-border-color="'.$modSettings['fb_slide_box_borderc'].'" data-stream="'.$modSettings['fb_slide_box_show_stream'].'" data-header="'.$modSettings['fb_slide_box_show_header'].'"></div>
						</div>
					</div>
				</div>';
			}
		}
		elseif($modSettings['fb_slide_box_mode'] == "iframe")
		{
			if($modSettings['fb_slide_box_show_after'] == "mouseenter")
			{
			$context['insert_after_template'] .= '
			<!--Facebook Slide Like Box '.FBSlideLikeBox_ver.' iframe-->
				<div id="FBSlideLikeBox_'.$modSettings['fb_slide_box_position'].'" title="'.$txt['fb_slide_box_find'].'"  onclick="location.href=\''.$modSettings['fb_slide_box_url'].'\';" style="cursor:pointer;">
					<div id="FBSlideLikeBox2_'.$modSettings['fb_slide_box_position'].'">
						<div id="FBSlideLikeBox3_'.$modSettings['fb_slide_box_position'].'">
							<iframe src="http://www.facebook.com/plugins/likebox.php?href='.$modSettings['fb_slide_box_url'].'&amp;width='.$modSettings['fb_slide_box_width'].'&amp;colorscheme='.$modSettings['fb_slide_box_colorscheme'].'&amp;show_faces='.$modSettings['fb_slide_box_show_faces'].'&amp;border_color=ffffff&amp;stream='.$modSettings['fb_slide_box_show_stream'].'&amp;header='.$modSettings['fb_slide_box_show_header'].'&amp;height='.$modSettings['fb_slide_box_height'].'" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:'.$modSettings['fb_slide_box_width'].'px; height:'.$modSettings['fb_slide_box_height'].'px;" allowTransparency="true"></iframe>
						</div>
					</div>
				</div>';
			}
			else
			{
			$context['insert_after_template'] .= '
			<!--Facebook Slide Like Box '.FBSlideLikeBox_ver.' iframe-->
				<div id="FBSlideLikeBox_'.$modSettings['fb_slide_box_position'].'" title="'.$txt['fb_slide_box_find'].'">
					<div id="FBSlideLikeBox2_'.$modSettings['fb_slide_box_position'].'">
						<div id="FBSlideLikeBox3_'.$modSettings['fb_slide_box_position'].'">
							<iframe src="http://www.facebook.com/plugins/likebox.php?href='.$modSettings['fb_slide_box_url'].'&amp;width='.$modSettings['fb_slide_box_width'].'&amp;colorscheme='.$modSettings['fb_slide_box_colorscheme'].'&amp;show_faces='.$modSettings['fb_slide_box_show_faces'].'&amp;border_color=ffffff&amp;stream='.$modSettings['fb_slide_box_show_stream'].'&amp;header='.$modSettings['fb_slide_box_show_header'].'&amp;height='.$modSettings['fb_slide_box_height'].'" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:'.$modSettings['fb_slide_box_width'].'px; height:'.$modSettings['fb_slide_box_height'].'px;" allowTransparency="true"></iframe>
						</div>
					</div>
				</div>';
			}
		}
		else{}
	}
}

//Settings
function FBSlideLikeBox_settings(&$return_config = false)
{
	global $context, $txt, $scripturl, $sourcedir, $settings;

	$context['page_title'] = $txt['fb_slide_box_admin'];
	$context['settings_title'] = $txt['fb_slide_box_admin'];
	$context['settings_message'] = $txt['fb_slide_box_admin_desc'];
	$context['settings_insert_below'] = '<div style="text-align:center;">Like this mod?<br />
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
		<input type="hidden" name="cmd" value="_s-xclick">
		<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
		<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
		<img alt="" border="0" src="https://www.paypalobjects.com/pl_PL/i/scr/pixel.gif" width="1" height="1">
		</form><br />Facebook Slide Like Box '.FBSlideLikeBox_ver.'</div>';
	$context['post_url'] = $scripturl . '?action=admin;area=modsettings;save;sa=FBSlideBox';

	$context['html_headers'] .= '
	<script type="text/javascript"><!-- // --><![CDATA[
	var FBSlideBoxUpd = function ()
	{
		document.getElementById("fb_slide_box_appId").disabled = document.getElementById("fb_slide_box_mode").value == "iframe";
		document.getElementById("fb_slide_box_locale").disabled = document.getElementById("fb_slide_box_mode").value == "iframe";
	}
	addLoadEvent(FBSlideBoxUpd);
	// ]]></script>';

	$config_vars = array(
		array('check', 'fb_slide_box_enable'),
		array('check', 'fb_slide_box_mobile'),
		array('select', 'fb_slide_box_mode', array('html5' => $txt['fb_slide_box_mode_html5'], 'iframe' => $txt['fb_slide_box_mode_iframe'],), 'onchange' => 'FBSlideBoxUpd();'),
		array('int', 'fb_slide_box_appId', 'subtext' => $txt['fb_slide_box_mode_warn'], 50), /* Only in HTML5 */
		array('select', 'fb_slide_box_locale', 'subtext' => $txt['fb_slide_box_mode_warn'], array('en_US' => 'English (US) (Default)', 'af_ZA' => 'Afrikaans', 'sq_AL' => 'Albanian', 'ar_AR' => 'Arabic', 'hy_AM' => 'Armenian', 'az_AZ' => 'Azerbaijani', 'eu_ES' => 'Basque', 'be_BY' => 'Belarusian', 'bg_BG' => 'Bulgarian', 'bn_IN' => 'Bengali', 'bs_BA' => 'Bosnian', 'ca_ES' => 'Catalan', 'hr_HR' => 'Croatian', 'cs_CZ' => 'Czech', 'da_DK' => 'Danish', 'de_DE' => 'German', 'el_GR' => 'Greek', 'en_GB' => 'English (UK)', 'en_PI' => 'English (Pirate)', 'en_UD' => 'English (Upside Down)', 'eo_EO' => 'Esperanto', 'es_ES' => 'Spanish (Spain)', 'es_LA' => 'Spanish', 'et_EE' => 'Estonian', 'fa_IR' => 'Persian', 'fb_LT' => 'Leet Speak', 'fi_FI' => 'Finnish', 'fo_FO' => 'Faroese', 'fr_CA' => 'French (Canada)', 'fr_FR' => 'French (France)', 'fy_NL' => 'Frisian', 'ga_IE' => 'Irish', 'gl_ES' => 'Galician', 'he_IL' => 'Hebrew', 'hi_IN' => 'Hindi', 'hu_HU' => 'Hungarian', 'id_ID' => 'Indonesian', 'is_IS' => 'Icelandic', 'it_IT' => 'Italian', 'ja_JP' => 'Japanese', 'ka_GE' => 'Georgian', 'km_KH' => 'Khmer', 'ko_KR' => 'Korean', 'ku_TR' => 'Kurdish', 'la_VA' => 'Latin', 'lt_LT' => 'Lithuanian', 'lv_LV' => 'Latvian', 'mk_MK' => 'Macedonian', 'ml_IN' => 'Malayalam', 'ms_MY' => 'Malay', 'nb_NO' => 'Norwegian (bokmal)', 'ne_NP' => 'Nepali', 'nl_NL' => 'Dutch', 'nn_NO' => 'Norwegian (nynorsk)', 'pa_IN' => 'Punjabi', 'pl_PL' => 'Polish', 'ps_AF' => 'Pashto', 'pt_BR' => 'Portuguese (Brazil)', 'pt_PT' => 'Portuguese (Portugal)', 'ro_RO' => 'Romanian', 'ru_RU' => 'Russian', 'sk_SK' => 'Slovak', 'sl_SI' => 'Slovenian', 'sr_RS' => 'Serbian', 'sv_SE' => 'Swedish', 'sw_KE' => 'Swahili', 'ta_IN' => 'Tamil', 'te_IN' => 'Telugu', 'th_TH' => 'Thai', 'tl_PH' => 'Filipino', 'tr_TR' => 'Turkish', 'uk_UA' => 'Ukrainian', 'cy_GB' => 'Welsh', 'vi_VN' => 'Vietnamese', 'zh_CN' => 'Simplified Chinese (China)', 'zh_HK' => 'Traditional Chinese (Hong Kong)', 'zh_TW' => 'Traditional Chinese (Taiwan)',)), /* Only in HTML5 */
		array('text', 'fb_slide_box_actions', 50),
		array('text', 'fb_slide_box_url', 50),
		array('select', 'fb_slide_box_image_name', FBSlideLikeBox_images($settings['default_theme_dir'].'/images/FBSlideLikeBox/')),
		array('select', 'fb_slide_box_position', array('left' => $txt['fb_slide_box_left'], 'right' => $txt['fb_slide_box_right'],)),
		array('select', 'fb_slide_box_show_after', array('click' => $txt['fb_slide_box_show_after_click'], 'mouseenter' => $txt['fb_slide_box_show_after_mouseenter'],)),
		array('check', 'fb_slide_box_disable_animation'),
		array('select', 'fb_slide_box_open_animation', array('slow' => $txt['fb_slide_box_slow'], 'normal' => $txt['fb_slide_box_normal'], 'fast' => $txt['fb_slide_box_fast'],)),
		array('select', 'fb_slide_box_close_animation', array('slow' => $txt['fb_slide_box_slow'], 'normal' => $txt['fb_slide_box_normal'], 'fast' => $txt['fb_slide_box_fast'],)),
		array('text', 'fb_slide_box_backgroundc', 10),
		array('text', 'fb_slide_box_borderc', 10),
		array('int', 'fb_slide_box_width', 'postinput' => $txt['fb_slide_box_px'], 3),
		array('int', 'fb_slide_box_height', 'postinput' => $txt['fb_slide_box_px'], 3),
		array('int', 'fb_slide_box_distance', 3),
		array('select', 'fb_slide_box_distance2', array('%' => $txt['fb_slide_box_pr'], 'px' => $txt['fb_slide_box_px'],)),
		array('select', 'fb_slide_box_colorscheme',	array('light' => $txt['fb_slide_box_colorscheme_light'], 'dark' => $txt['fb_slide_box_colorscheme_dark'],)),
		array('select', 'fb_slide_box_show_faces', array('true' => $txt['fb_slide_box_yes'], 'false' => $txt['fb_slide_box_no'],)),
		array('select', 'fb_slide_box_show_stream',	array('true' => $txt['fb_slide_box_yes'], 'false' => $txt['fb_slide_box_no'],)),
		array('select', 'fb_slide_box_show_header',	array('true' => $txt['fb_slide_box_yes'], 'false' => $txt['fb_slide_box_no'],)),
		array('title', 'fb_slide_box_permissions'),
		array('desc', 'fb_slide_box_permissions_desc'),
		array('permissions', 'FBSlideLikeBox_display'),
		array('permissions', 'FBSlideLikeBox_user_disable'),
	);

	if ($return_config)
		return $config_vars;

	if (isset($_GET['save'])) {
		checkSession();
		saveDBSettings($config_vars);
		writeLog();
		redirectexit('action=admin;area=modsettings;sa=FBSlideBox');
	}
	prepareDBSettingContext($config_vars);
}

// few permissions
function FBSlideLikeBox_permissions(&$permissionGroups, &$permissionList)
{
	$permissionList['membergroup']['FBSlideLikeBox_display'] = array(false, 'FBSlideLikeBox_classic', 'FBSlideLikeBox_simple');
	$permissionList['membergroup']['FBSlideLikeBox_user_disable'] = array(false, 'FBSlideLikeBox_classic', 'FBSlideLikeBox_simple');
	$permissionGroups['membergroup']['simple'] = array('FBSlideLikeBox_simple');
	$permissionGroups['membergroup']['classic'] = array('FBSlideLikeBox_classic');
}

// Scan image directory, find all FB icons and show result as array
function FBSlideLikeBox_images($directory)
{
    $results = array();
    $handler = opendir($directory);

	while ($file = readdir($handler))
	{
		// if file isn't this directory or its parent, add it to the results
		if ($file != "." && $file != "..")
		{
			$extension = pathinfo($file, PATHINFO_EXTENSION);
			$allowed_files = array('png','gif','jpeg','jpg');
			
			if(in_array($extension,$allowed_files)){
				$results[$file] = $file;
			}
		}
	}
    closedir($handler);
	return $results;
}
/*
function FBSlideLikeBox_menu_buttons()
{
	global $context;
	
	if ($context['current_action'] == 'credits')
		$context['copyrights']['mods'][] = '<a href="#"  target="_blank" title="">Facebook Slide Like Box</a> &copy phantom';
}*/

?>