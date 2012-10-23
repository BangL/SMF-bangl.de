<?php
/**
 * TS3 Web Viewer Class - PHP 5.2.2 or higher is required!
 * only use with beta23 or higher!
 *
 * @author     Branko Wilhelm <bw@z-index.net>
 * @link       http://www.z-index.net
 * @copyright  2010 - 2011 Branko Wilhelm
 * @license    GNU Public License <http://www.gnu.org/licenses/gpl.html>
 * @package    ts3.web.viewer.class
 * @version    $Id: example.php 40 2011-10-06 08:42:27Z bRunO $
 */
$cfg['ts3_host'] = '127.0.0.1';
$cfg['ts3_query'] = 10011;
//$cfg['ts3_sid']   = 1;
$cfg['ts3_port'] = 9987;

/*
 * NOTE: Access without Serverlogin is only possible if the following rights of the Servergroup "Guest" set
 *
 * b_virtualserver_select
 * b_virtualserver_info_view
 * b_virtualserver_channel_list
 * b_virtualserver_client_list
 */

error_reporting(0);  // ini_set('display_errors', 1); // only for this example!

require('class.ts3viewer.php');

$ts3 = new TS3Viewer($cfg['ts3_host'], $cfg['ts3_query'], null, $cfg['ts3_port']);      // select Server by UDP Port
// $ts3 = new TS3Viewer($cfg['ts3_host'], $cfg['ts3_query'], $cfg['ts3_sid']);          // select Server by ServerID
// $ts3->serverlogin = array('login' => 'uSeRnAmE', 'password' => 'pAsSwOrD');          // ServerQuery Login can be created on "Extras/Tools" -> "ServerQuery Login"

// $ts3->socket_timeout = 10;                                                           // set custom socket timeout
// $ts3->img_path = '../images/ts3/';                                                   // set custom images path - do not use realpath()!
// $ts3->cache_path = '../cache/';                                                      // set custom cache path (if filecache used)
// $ts3->cache_handler = 'APC';                                                         // set cache handler directly to APC (Memcache, Xcache, APC & File are possible)
// $ts3->cache_timeout = 120;                                                           // set cache timeout to 2 minutes
// $ts3->wildcard = null;                                                               // remove every first wildcard in channel tree
// $ts3->player_trim = 15;                                                              // strip player names at after 15 chars
// $ts3->chide['private'] = true;                                                       // hide channel "private" and all subchannels
// $ts3->icons['client_is_recording'] = 1;                                              // add "player records" icon (required a icon named "client_is_recording.png")
// $ts3->lang['stats']['traffic'] = 'Traffic:';                                         // change statistik language
// unset($ts3->icons['client_flag_talking']);                                           // remove "player is talking" icon
// unset($ts3->groups['sgroup']['6']);                                                  // remove "Server Admin" icon
// $ts3->groups['sgroup'][7] = array('n' => 'Member', 'p' => 'member.png');             // give Servergroup ID #7 (Normal) a name and icon
// $ts3->groups['sgroup'][8] = array('n' => 'Guest',  'p' => 'guest.png');              // give Servergroup ID #8 (Guest) a name and icon

$ts3->build(); // build the content
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>TS3Viewer Example - <?php echo $ts3->title(); ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta http-equiv="refresh" content="30; URL=<?php echo $_SERVER['PHP_SELF']; ?>" />
        <link rel="stylesheet" type="text/css" href="stylesheet.css" />
    </head>
    <body>
        <!--<table cellpadding="2" cellspacing="12" align="center" class="ts3table">
            <thead>
                <tr>
                    <th colspan="2" align="center">
                        <h2><?php //echo $ts3->title(); ?></h2>
                        <?php //echo $ts3->banner(); ?>
                        <h3><?php //echo $ts3->website(); ?></h3>
                    </th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <td colspan="2" align="center">
                        this example contains images from <a href="http://www.teamspeak.com" target="_blank">www.teamspeak.com</a>.
                        <br /> <br />
                        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
                            <input type="hidden" name="cmd" value="_s-xclick" />
                            <input type="hidden" name="hosted_button_id" value="4MVUJDMZASTAG" />
                            <input type="image" src="https://www.paypalobjects.com/WEBSCR-640-20110401-1/en_US/i/btn/btn_donate_LG.gif" name="submit" alt="PayPal - The safer, easier way to pay online!" />
                            <img alt="" src="https://www.paypalobjects.com/WEBSCR-640-20110401-1/de_DE/i/scr/pixel.gif" width="1" height="1" />
                        </form>
                    </td>
                </tr>
            </tfoot>
            <tbody>
                <tr>
                    <td valign="top" class="bg">-->
                        <div style="float:left"><b><?php echo $ts3->tree_head(); ?></b></div>
                        <div style="float:right"><b><?php echo $ts3->useron(); ?></b></div>
                        <br style="clear:both"/>
                        <?php echo $ts3->tree(); ?>
                    <!--</td>
                    <td width="50%" valign="top" class="bg">
                        <div align="center"><b>Statistik</b></div>
                        <?php //echo $ts3->stats(); ?>
                        <br />
                        <div align="center"><b>Legende</b></div>
                        <?php //echo $ts3->legend(); ?>
                    </td>
                </tr>
            </tbody>
        </table>-->
    </body>
</html>