<?php 

define('IN_MYBB', 1); require "./global.php";
add_breadcrumb("Reputation Lock", "reputationlock.php"); 
$lang->load('reputation_lock');

if($mybb->settings['reputationlock_enable'] != 1)
{
	return;
}

// Check what usergroup before entering page
$groups_allowed = explode(',', $mybb->settings['reputationlock_groups']);
if(!in_array($mybb->user['usergroup'], $groups_allowed) && $mybb->settings['reputationlock_groups'] != '-1')
{
	error_no_permission();
}

// Decide what actual button will say (Enable Lock) or Disable Lock
$replock = '';
$user = get_user($mybb->user['uid']);
if($user['reputationlocked'] != 0)
{
	$replock = $lang->reputationlock_disable;
} else {
	$replock = $lang->reputationlock_enable;
}

// If submit, check usergroup, update setting
if(isset($mybb->input['lockrep']) && $mybb->request_method == "post")
{
verify_post_check($mybb->get_input('my_post_key'));

// Lets double a few things
$groups_allowed = explode(',', $mybb->settings['reputationlock_groups']);
if(!in_array($mybb->user['usergroup'], $groups_allowed) && $mybb->settings['reputationlock_groups'] != '-1')
{
	error_no_permission();
} elseif($mybb->user['uid'] != $mybb->get_input("uid"))
{
	error_no_permission();
}

// If already set, we're disabling lock
$useruid = $user['uid'];
if($user['reputationlocked'] == 1)
{
	$entry = array(
	
	"reputationlocked" => '0'
	);
	$db->update_query("users", $entry, "uid = $useruid");
} else {
	// Fair well reputation system
	$entry = array(
	
	"reputationlocked" => 1
	);
	$db->update_query("users", $entry, "uid = $useruid");
}
$finalerror = "$lang->reputationlock_redirect1<br /><br /> $lang->reputationlock_redirect2";
	redirect("reputationlock.php", "$finalerror");
}

eval("\$html = \"".$templates->get("reputation_lock")."\";"); 

output_page($html);

?>