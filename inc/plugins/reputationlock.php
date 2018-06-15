<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}
$plugins->add_hook('reputation_do_add_start', 'reputationlock_block');
$plugins->add_hook('reputation_start', 'reputationlock_block');

function reputationlock_info()
{
global $lang;
$lang->load('reputation_lock');

	return array(
        "name"  => $lang->reputationlock_plugintitle,
        "description"=> $lang->reputationlock_plugindesc,
        "website"        => "https://oseax.com",
        "author"        => "Wires <i>(AndreRl)</i>",
        "authorsite"    => "https://oseax.com",
        "version"        => "1.0",
        "guid"             => "",
        "compatibility" => "18*"
    );
}


function reputationlock_activate()
{
global $db, $mybb, $lang;

$lang->load('reputation_lock');

if(!$db->field_exists('reputationlocked', "users"))
{
	$db->add_column("users", "reputationlocked", "tinyint(1) NOT NULL");
}

$setting_group = array(
    'name' => 'reputationlock',
    'title' => $lang->reputationlock_title,
    'description' => $lang->reputationlock_desc,
    'disporder' => 5, 
    'isdefault' => 0
);

$gid = $db->insert_query("settinggroups", $setting_group);

$setting_array = array(

    'reputationlock_groups' => array(
        'title' => $lang->reputationlock_groupcontrol,
        'description' => $lang->reputationlock_gcdesc,
        'optionscode' => "groupselect",
        'value' => '',
        'disporder' => 2
    ),

    'reputationlock_enable' => array(
        'title' => $lang->reputationlock_enable,
        'description' => $lang->reputationlock_endesc,
        'optionscode' => 'yesno',
        'value' => 1,
        'disporder' => 1
    ),
);

foreach($setting_array as $name => $setting)
{
    $setting['name'] = $name;
    $setting['gid'] = $gid;

    $db->insert_query('settings', $setting);
}

rebuild_settings();

// Templates
$replocktemplate = '<html>
<head>
<title>{$mybb->settings[\'bbname\']} - Reputation Lock</title>
{$headerinclude}
</head>
<body>
{$header}
<table border="0" cellspacing="1" cellpadding="4" class="tborder">
<tr>
<td class="thead"><span class="smalltext"><strong>{$lang->reputationlock_title}</strong></span></td>
</tr>
<tr>
<td class="trow1">

{$lang->reputationlock_start}

<ul>
<li>{$lang->reputationlock_restrict}</li>
<li>{$lang->reputationlock_receive}</li>
<li>{$lang->reputationlock_view}</li>
</ul>

</td></tr></table> <br />
<center>

						<form method="post">
						<input type="hidden" name="my_post_key" value="{$mybb->post_code}"></input>
						<input type="hidden" name="uid" value="{$mybb->user[\'uid\']}"><br />
						<input type="submit" value="{$replock}" class="button" name="lockrep">
						</form>
</center>
<br />
{$footer}
</body>
</html>';

$reputation_lockerror = '<div class="modal">
	<div style="overflow-y: auto; max-height: 400px;">
      <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="trow1" style="padding: 20px">
			<strong>Error</strong><br /><br />
			   <blockquote>{$lang->reputationlock_error}</blockquote>
		</td>
	</tr>
</table>
  </div>
</div>';

$replock_array = array(
    'title' => 'reputation_lock',
    'template' => $db->escape_string($replocktemplate),
    'sid' => '-1',
    'version' => '',
    'dateline' => time()
);
$replockerror_array = array(
    'title' => 'reputation_lockerror',
    'template' => $db->escape_string($reputation_lockerror),
    'sid' => '-1',
    'version' => '',
    'dateline' => time()
);

$db->insert_query('templates', $replock_array);
$db->insert_query('templates', $replockerror_array);

}

function reputationlock_deactivate()
{
global $db;

$db->drop_column("users", "reputationlocked");
$db->delete_query('settings', "name IN ('reputationlock_enable', 'reputationlock_groups')");
$db->delete_query('settinggroups', "name = 'reputationlock'");
$db->delete_query("templates", "title IN ('reputation_lock', 'reputation_lockerror')");


rebuild_settings();
}

// Where all the checks are handled
function reputationlock_block()
{
global $db, $mybb, $templates, $lang;

$lang->load('reputation_lock');

if($mybb->settings['reputationlock_enable'] != 1)
{
	return;
}

// Block from receiving reputation
$uid = (int)$mybb->get_input('uid', MyBB::INPUT_INT);
$u = get_user($uid);

if($u['reputationlocked'] == 1)
{
	error($lang->reputationlock_lock);
}

// Block from giving reputation
if($mybb->input['action'] == "do_add" && $mybb->request_method == "post")
{
	$me = get_user($mybb->user['uid']);
	if($me['reputationlocked'] == 1)
	{
		eval("\$error = \"".$templates->get("reputation_lockerror", 1, 0)."\";");
		echo $error;
		exit;
	}
}

}

// Continue on main page...
