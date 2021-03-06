<?php
/*
	vpn_pppoe_edit.php
*/
/* ====================================================================
 *	Copyright (c)  2004-2015  Electric Sheep Fencing, LLC. All rights reserved.
 *
 *	Redistribution and use in source and binary forms, with or without modification,
 *	are permitted provided that the following conditions are met:
 *
 *	1. Redistributions of source code must retain the above copyright notice,
 *		this list of conditions and the following disclaimer.
 *
 *	2. Redistributions in binary form must reproduce the above copyright
 *		notice, this list of conditions and the following disclaimer in
 *		the documentation and/or other materials provided with the
 *		distribution.
 *
 *	3. All advertising materials mentioning features or use of this software
 *		must display the following acknowledgment:
 *		"This product includes software developed by the pfSense Project
 *		 for use in the pfSense software distribution. (http://www.pfsense.org/).
 *
 *	4. The names "pfSense" and "pfSense Project" must not be used to
 *		 endorse or promote products derived from this software without
 *		 prior written permission. For written permission, please contact
 *		 coreteam@pfsense.org.
 *
 *	5. Products derived from this software may not be called "pfSense"
 *		nor may "pfSense" appear in their names without prior written
 *		permission of the Electric Sheep Fencing, LLC.
 *
 *	6. Redistributions of any form whatsoever must retain the following
 *		acknowledgment:
 *
 *	"This product includes software developed by the pfSense Project
 *	for use in the pfSense software distribution (http://www.pfsense.org/).
 *
 *	THIS SOFTWARE IS PROVIDED BY THE pfSense PROJECT ``AS IS'' AND ANY
 *	EXPRESSED OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 *	IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 *	PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE pfSense PROJECT OR
 *	ITS CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 *	SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 *	NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 *	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 *	HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
 *	STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 *	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
 *	OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *	====================================================================
 *
 */

##|+PRIV
##|*IDENT=page-services-pppoeserver-edit
##|*NAME=Services: PPPoE Server: Edit
##|*DESCR=Allow access to the 'Services: PPPoE Server: Edit' page.
##|*MATCH=vpn_pppoe_edit.php*
##|-PRIV

require("guiconfig.inc");
require_once("vpn.inc");

function vpn_pppoe_get_id() {
	global $config;

	$vpnid = 1;
	if (is_array($config['pppoes']['pppoe'])) {
		foreach ($config['pppoes']['pppoe'] as $pppoe) {
			if ($vpnid == $pppoe['pppoeid']) {
				$vpnid++;
			} else {
				return $vpnid;
			}
		}
	}

	return $vpnid;
}

if (!is_array($config['pppoes']['pppoe'])) {
	$config['pppoes']['pppoe'] = array();
}

$a_pppoes = &$config['pppoes']['pppoe'];

if (is_numericint($_GET['id'])) {
	$id = $_GET['id'];

if (isset($_POST['id']) && is_numericint($_POST['id']))
	$id = $_POST['id'];
}

if (isset($id) && $a_pppoes[$id]) {
	$pppoecfg =& $a_pppoes[$id];

	$pconfig['remoteip'] = $pppoecfg['remoteip'];
	$pconfig['localip'] = $pppoecfg['localip'];
	$pconfig['mode'] = $pppoecfg['mode'];
	$pconfig['interface'] = $pppoecfg['interface'];
	$pconfig['n_pppoe_units'] = $pppoecfg['n_pppoe_units'];
	$pconfig['pppoe_subnet'] = $pppoecfg['pppoe_subnet'];
	$pconfig['pppoe_dns1'] = $pppoecfg['dns1'];
	$pconfig['pppoe_dns2'] = $pppoecfg['dns2'];
	$pconfig['descr'] = $pppoecfg['descr'];
	$pconfig['username'] = $pppoecfg['username'];
	$pconfig['pppoeid'] = $pppoecfg['pppoeid'];
	if (is_array($pppoecfg['radius'])) {
		$pconfig['radacct_enable'] = isset($pppoecfg['radius']['accounting']);
		$pconfig['radiusissueips'] = isset($pppoecfg['radius']['radiusissueips']);
		if (is_array($pppoecfg['radius']['server'])) {
			$pconfig['radiusenable'] = isset($pppoecfg['radius']['server']['enable']);
			$pconfig['radiusserver'] = $pppoecfg['radius']['server']['ip'];
			$pconfig['radiusserverport'] = $pppoecfg['radius']['server']['port'];
			$pconfig['radiusserveracctport'] = $pppoecfg['radius']['server']['acctport'];
			$pconfig['radiussecret'] = $pppoecfg['radius']['server']['secret'];
		}

		if (is_array($pppoecfg['radius']['server2'])) {
			$pconfig['radiussecenable'] = isset($pppoecfg['radius']['server2']['enable']);
			$pconfig['radiusserver2'] = $pppoecfg['radius']['server2']['ip'];
			$pconfig['radiusserver2port'] = $pppoecfg['radius']['server2']['port'];
			$pconfig['radiusserver2acctport'] = $pppoecfg['radius']['server2']['acctport'];
			$pconfig['radiussecret2'] = $pppoecfg['radius']['server2']['secret2'];
		}

		$pconfig['radius_nasip'] = $pppoecfg['radius']['nasip'];
		$pconfig['radius_acct_update'] = $pppoecfg['radius']['acct_update'];
	}
}

if ($_POST) {
	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	if ($_POST['mode'] == "server") {
		$reqdfields = explode(" ", "localip remoteip");
		$reqdfieldsn = array(gettext("Server address"), gettext("Remote start address"));

		if ($_POST['radiusenable']) {
			$reqdfields = array_merge($reqdfields, explode(" ", "radiusserver radiussecret"));
			$reqdfieldsn = array_merge($reqdfieldsn,
				array(gettext("RADIUS server address"), gettext("RADIUS shared secret")));
		}

		do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

		if (($_POST['localip'] && !is_ipaddr($_POST['localip']))) {
			$input_errors[] = gettext("A valid server address must be specified.");
		}
		if (($_POST['pppoe_subnet'] && !is_ipaddr($_POST['remoteip']))) {
			$input_errors[] = gettext("A valid remote start address must be specified.");
		}
		if (($_POST['radiusserver'] && !is_ipaddr($_POST['radiusserver']))) {
			$input_errors[] = gettext("A valid RADIUS server address must be specified.");
		}

		$_POST['remoteip'] = $pconfig['remoteip'] = gen_subnet($_POST['remoteip'], $_POST['pppoe_subnet']);
		$subnet_start = ip2ulong($_POST['remoteip']);
		$subnet_end = ip2ulong($_POST['remoteip']) + $_POST['pppoe_subnet'] - 1;
		if ((ip2ulong($_POST['localip']) >= $subnet_start) &&
			(ip2ulong($_POST['localip']) <= $subnet_end)) {
			$input_errors[] = gettext("The specified server address lies in the remote subnet.");
		}
		if ($_POST['localip'] == get_interface_ip($_POST['interface'])) {
			$input_errors[] = gettext("The specified server address is equal to an interface ip address.");
		}

		for ($x = 0; $x < 4999; $x++) {
			if ($_POST["username{$x}"]) {
				if (empty($_POST["password{$x}"])) {
					$input_errors[] = sprintf(gettext("No password specified for username %s"), $_POST["username{$x}"]);
				}
				if ($_POST["ip{$x}"] != "" && !is_ipaddr($_POST["ip{$x}"])) {
					$input_errors[] = sprintf(gettext("Incorrect ip address specified for username %s"), $_POST["username{$x}"]);
				}
			}
		}
	}

	if ($_POST['pppoeid'] && !is_numeric($_POST['pppoeid'])) {
		$input_errors[] = gettext("Wrong data submitted");
	}

	if (!$input_errors) {
		$pppoecfg = array();

		$pppoecfg['remoteip'] = $_POST['remoteip'];
		$pppoecfg['localip'] = $_POST['localip'];
		$pppoecfg['mode'] = $_POST['mode'];
		$pppoecfg['interface'] = $_POST['interface'];
		$pppoecfg['n_pppoe_units'] = $_POST['n_pppoe_units'];
		$pppoecfg['pppoe_subnet'] = $_POST['pppoe_subnet'];
		$pppoecfg['descr'] = $_POST['descr'];
		if ($_POST['radiusserver'] || $_POST['radiusserver2']) {
			$pppoecfg['radius'] = array();

			$pppoecfg['radius']['nasip'] = $_POST['radius_nasip'];
			$pppoecfg['radius']['acct_update'] = $_POST['radius_acct_update'];
		}

		if ($_POST['radiusserver']) {
			$pppoecfg['radius']['server'] = array();

			$pppoecfg['radius']['server']['ip'] = $_POST['radiusserver'];
			$pppoecfg['radius']['server']['secret'] = $_POST['radiussecret'];
			$pppoecfg['radius']['server']['port'] = $_POST['radiusserverport'];
			$pppoecfg['radius']['server']['acctport'] = $_POST['radiusserveracctport'];
		}

		if ($_POST['radiusserver2']) {
			$pppoecfg['radius']['server2'] = array();

			$pppoecfg['radius']['server2']['ip'] = $_POST['radiusserver2'];
			$pppoecfg['radius']['server2']['secret2'] = $_POST['radiussecret2'];
			$pppoecfg['radius']['server2']['port'] = $_POST['radiusserver2port'];
			$pppoecfg['radius']['server2']['acctport'] = $_POST['radiusserver2acctport'];
		}

		if ($_POST['pppoe_dns1'] <> "") {
			$pppoecfg['dns1'] = $_POST['pppoe_dns1'];
		}

		if ($_POST['pppoe_dns2'] <> "") {
			$pppoecfg['dns2'] = $_POST['pppoe_dns2'];
		}

		if ($_POST['radiusenable'] == "yes") {
			$pppoecfg['radius']['server']['enable'] = true;
		}

		if ($_POST['radiussecenable'] == "yes") {
			$pppoecfg['radius']['server2']['enable'] = true;
		}

		if ($_POST['radacct_enable'] == "yes") {
			$pppoecfg['radius']['accounting'] = true;
		}

		if ($_POST['radiusissueips'] == "yes") {
			$pppoecfg['radius']['radiusissueips'] = true;
		}

		if ($_POST['pppoeid']) {
			$pppoecfg['pppoeid'] = $_POST['pppoeid'];
		} else {
			$pppoecfg['pppoeid'] = vpn_pppoe_get_id();
		}

		$users = array();
		for ($x = 0; $x < 4999; $x++) {
			if ($_POST["username{$x}"]) {
				$usernam = $_POST["username{$x}"] . ":" . base64_encode($_POST["password{$x}"]);
				if ($_POST["ip{$x}"]) {
					$usernam .= ":" . $_POST["ip{$x}"];
				}

				$users[] = $usernam;
			}
		}

		if (count($users) > 0) {
			$pppoecfg['username'] = implode(" ", $users);
		}

		if (!isset($id)) {
			$id = count($a_pppoes);
		}

		if (file_exists("{$g['tmp_path']}/.vpn_pppoe.apply")) {
			$toapplylist = unserialize(file_get_contents("{$g['tmp_path']}/.vpn_pppoe.apply"));
		} else {
			$toapplylist = array();
		}

		$toapplylist[] = $pppoecfg['pppoeid'];
		$a_pppoes[$id] = $pppoecfg;

		write_config();
		mark_subsystem_dirty('vpnpppoe');
		file_put_contents("{$g['tmp_path']}/.vpn_pppoe.apply", serialize($toapplylist));
		header("Location: vpn_pppoe.php");
		exit;
	}
}

function build_interface_list() {
	$list = array();

	$interfaces = get_configured_interface_with_descr();

	foreach ($interfaces as $iface => $ifacename)
		$list[$iface] = $ifacename;

	return($list);
}

$pgtitle = array(gettext("Services"),gettext("PPPoE Server"), gettext("Edit"));
$shortcut_section = "pppoes";
include("head.inc");

if ($input_errors)
	print_input_errors($input_errors);

if ($savemsg)
	print_info_box($savemsg, 'success');

$form = new Form();

$section = new Form_Section('PPPoE Server Configuration');

$section->addInput(new Form_Checkbox(
	'mode',
	'Enable',
	'Enable PPPoE Server',
	($pconfig['mode'] == "server"),
	'server'
)) ->toggles('.form-group:not(:first-child)');

$section->addInput(new Form_Select(
	'interface',
	'Interface',
	$pconfig['interface'],
	build_interface_list()

));

$section->addInput(new Form_Select(
	'pppoe_subnet',
	'Subnet mask',
	$pconfig['pppoe_subnet'],
	array_combine(range(0, 32, 1), range(0, 32, 1))
))->setHelp('Hint: 24 is 255.255.255.0');

$section->addInput(new Form_Select(
	'n_pppoe_units',
	'No. of PPPoE Users',
	$pconfig['n_pppoe_units'],
	array_combine(range(0, 255, 1), range(0, 255, 1))
));

$section->addInput(new Form_IpAddress(
	'localip',
	'Server Address',
	$pconfig['localip']
))->setHelp('Enter the IP address the PPPoE server should give to clients for use as their "gateway"' . '<br />' .
			'Typically this is set to an unused IP just outside of the client range '. '<br />' .
			'NOTE: This should NOT be set to any IP address currently in use on this firewall');

$section->addInput(new Form_IpAddress(
	'remoteip',
	'Remote Address Range',
	$pconfig['remoteip']
))->setHelp('Specify the starting address for the client IP address subnet');

$section->addInput(new Form_Input(
	'descr',
	'Description',
	'text',
	$pconfig['descr']
));

$section->addInput(new Form_Input(
	'pppoe_dns1',
	'DNS Servers',
	'text',
	$pconfig['pppoe_dns1']
));

$section->addInput(new Form_IpAddress(
	'pppoe_dns2',
	null,
	$pconfig['pppoe_dns2']
))->setHelp('If entered these servers will be given to all PPPoE clients, otherwise LAN DNS and one WAN DNS will go to all clients');

$section->addInput(new Form_Checkbox(
	'radiusenable',
	'RADIUS',
	'Use a RADIUS Server for authentication',
	$pconfig['radiusenable']
))->setHelp('All users will be authenticated using the RADIUS server specified below. The local user database ' .
			'will not be used');

$section->addInput(new Form_Checkbox(
	'radacct_enable',
	null,
	'Enable RADIUS Accounting',
	$pconfig['radacct_enable']
))->setHelp('Sends accounting packets to the RADIUS server');

$section->addInput(new Form_Checkbox(
	'radiussecenable',
	null,
	'Use backup RADIUS server',
	$pconfig['radiussecenable']
))->setHelp('If primary server fails all requests will be sent via backup server');

$section->addInput(new Form_IpAddress(
	'radius_nasip',
	'NAS IP Address',
	$pconfig['radius_nasip']
))->setHelp('RADIUS server NAS IP Address');

$section->addInput(new Form_Input(
	'radius_acct_update',
	'RADIUS Accounting Update',
	'text',
	$pconfig['radius_acct_update']
))->setHelp('RADIUS accounting update period in seconds');

$section->addInput(new Form_Checkbox(
	'radiusissueips',
	'Radius Issued IPs',
	'Issue IP Addresses via RADIUS server',
	$pconfig['radiusissueips']
));

$group = new Form_Group('RADIUS server Primary');

$group->add(new Form_IpAddress(
	'radiusserver',
	null,
	$pconfig['radiusserver']
))->setHelp('IP Address');

$group->add(new Form_Input(
	'radiusserverport',
	null,
	'text',
	$pconfig['radiusserverport']
))->setHelp('Authentication port ');

$group->add(new Form_Input(
	'radiusserveracctport',
	null,
	'text',
	$pconfig['radiusserveracctport']
))->setHelp('Accounting port (optional)');

$group->setHelp('Standard ports are 1812 (authentication) and 1813 (accounting)');

$section->add($group);

$section->addInput(new Form_Input(
	'radiussecret',
	'RADIUS primary shared secret',
	'password',
	$pconfig['radiussecret']
))->setHelp('Enter the shared secret that will be used to authenticate to the RADIUS server.');

$group = new Form_Group('RADIUS server Secondary');

$group->add(new Form_IpAddress(
	'radiusserver2',
	null,
	$pconfig['radiusserver2']
))->setHelp('IP Address');

$group->add(new Form_Input(
	'radiusserver2port',
	null,
	'text',
	$pconfig['radiusserver2port']
))->setHelp('Authentication port ');

$group->add(new Form_Input(
	'radiusserver2acctport',
	null,
	'text',
	$pconfig['radiusserver2acctport']
))->setHelp('Accounting port (optional)');

$group->setHelp('Standard ports are 1812 (authentication) and 1813 (accounting)');

$section->add($group);

$section->addInput(new Form_Input(
	'radiussecret2',
	'RADIUS secondary shared secret',
	'password',
	$pconfig['radiussecret2']
))->setHelp('Enter the shared secret that will be used to authenticate to the backup RADIUS server.');

$counter = 0;
$numrows = count($item) -1;

$usernames = $pconfig['username'];

//DEBUG
//$usernames = 'sbeaver:TXlQYXNzd2Q=:192.168.1.1 smith:TXlQYXNzd2Q=:192.168.2.1 sjones:TXlQYXNzd2Q=:192.168.3.1 salpha:TXlQYXNzd2Q=:192.168.4.1';

if($usernames == "")
	$usernames = '::';

if ($usernames != ""){
	$item = explode(" ", $usernames);

	$numrows = count($item) -1;

	foreach($item as $ww) {
		$wws = explode(":", $ww);
		$user = $wws[0];
		$passwd = base64_decode($wws[1]);
		$ip = $wws[2];

		$group = new Form_Group($counter == 0 ? 'User table':null);
		$group->addClass('repeatable');

		$group->add(new Form_Input(
			'username' . $counter,
			null,
			'text',
			$user
		))->setHelp($numrows == $counter ? 'User name':null);

		$group->add(new Form_Input(
			'password' . $counter,
			null,
			'password',
			$passwd
		))->setHelp($numrows == $counter ? 'Password':null);

		$group->add(new Form_IpAddress(
			'ip' . $counter,
			null,
			$ip
		))->setHelp($numrows == $counter ? 'IP Address':null);

		$group->add(new Form_Button(
			'deleterow' . $counter,
			'Delete'
		))->removeClass('btn-primary')->addClass('btn-warning');

		$section->add($group);

		$counter++;
	}
}

$btnaddrow = new Form_Button(
	'addrow',
	'Add user'
);

$btnaddrow->removeClass('btn-primary')->addClass('btn-success');

$section->addInput(new Form_StaticText(
	null,
	'&nbsp;' . $btnaddrow
));

// Hidden fields
if(isset($id)) {
	$section->addInput(new Form_Input(
		'id',
		null,
		'hidden',
		htmlspecialchars($id, ENT_QUOTES | ENT_HTML401)
	));
}

if (isset($pconfig['pppoeid'])) {
	$section->addInput(new Form_Input(
		'pppoeid',
		null,
		'hidden',
		$pconfig['pppoeid']
	));
}

$form->add($section);

print($form);

print_info_box(gettext('Don\'t forget to add a firewall rule to permit traffic from PPPoE clients'));
?>
<script>
//<![CDATA[
events.push(function(){

	// show/hide radius server controls
	function hide_radius(hide) {
		disableInput('radacct_enable', hide);
		disableInput('radiusserver', hide);
		disableInput('radiussecret', hide);
		disableInput('radiusserverport', hide);
		disableInput('radiusserveracctport', hide);
		disableInput('radiusissueips', hide);
		disableInput('radius_nasip', hide);
		disableInput('radiusissueips', hide);
		disableInput('radius_nasip', hide);
		disableInput('radius_acct_update', hide);
		disableInput('radiussecenable', hide);
		hide_radius2(hide);
	}
	// show/hide radius server 2 controls
	function hide_radius2(hide) {
		disableInput('radiusserver2', hide);
		disableInput('radiussecret2', hide);
		disableInput('radiusserver2port', hide);
		disableInput('radiusserver2acctport', hide);
	}

	// When the RADIUS checkbox is clicked . .
	$('#radiusenable').click(function () {
		hide_radius(!$('#radiusenable').prop('checked'));
		if(!$('#radiusenable').prop('checked'))
			hide_radius2(true);
		else
			hide_radius2(!$('#radiussecenable').prop('checked'));
	});

	// When the 'Use backup RADIUS' checkbox is clicked . .
	$('#radiussecenable').click(function () {
		hide_radius2(!$('#radiussecenable').prop('checked'));
	});

	// ---------- On initial page load ------------------------------------------------------------
	hide_radius2(!$('#radiussecenable').prop('checked'));
	hide_radius(!$('#radiusenable').prop('checked'));

	// Suppress "Delete row" button if there are fewer than two rows
	checkLastRow();

});
//]]>
</script>
<?php
include("foot.inc");
