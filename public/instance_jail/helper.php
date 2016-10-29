<?php
//store real path
$modulepath=realpath('');
//move back to root;
chdir("../");

require_once('./head.inc.php');
require_once('./cbsd.inc.php');

require_once('./left_menu.inc.php');
?>

<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
        <h2 class="sub-header">Helpers</h2>

<?php

include_once($modulepath.'/db.php');
$tplfile=$rp.'/jailtpl.jconf';


function xgetJailTemplate()
{
        global $jname, $host_hostname, $astart, $ip4_addr, $workdir;
        global $mount_devfs, $allow_mount, $allow_devfs, $allow_nullfs;
        global $mkhostsfile, $devfs_ruleset, $ver, $baserw, $mount_src, $mount_obj, $mount_kernel;
        global $mount_ports, $astart, $vnet, $applytpl, $mdsize, $floatresolv;
        global $pkg_bootstrap, $user_pw_root, $interface, $sysrc_enable;
        global $with_img_helpers, $runasap;
	global $tplfile;

        $file=file_get_contents($tplfile);

        if(!empty($file))
        {
                $file=str_replace('#jname#',$jname,$file);
                $file=str_replace('#host_hostname#',$host_hostname,$file);
                $file=str_replace('#astart#',$astart,$file);
                $file=str_replace('#ip4_addr#',$ip4_addr,$file);
                $file=str_replace('#workdir#',$workdir,$file);

                $file=str_replace('#mount_devfs#',$mount_devfs,$file);
                $file=str_replace('#allow_mount#',$allow_mount,$file);
                $file=str_replace('#allow_devfs#',$allow_devfs,$file);
                $file=str_replace('#allow_nullfs#',$allow_nullfs,$file);

                $file=str_replace('#mkhostsfile#',$mkhostsfile,$file);
                $file=str_replace('#devfs_ruleset#',$devfs_ruleset,$file);
                $file=str_replace('#ver#',$ver,$file);
                $file=str_replace('#baserw#',$baserw,$file);
                $file=str_replace('#mount_obj#',$mount_obj,$file);
                $file=str_replace('#mount_kernel#',$mount_kernel,$file);

                $file=str_replace('#mount_ports#',$mount_ports,$file);
                $file=str_replace('#mount_src#',$mount_src,$file);
                $file=str_replace('#astart#',$astart,$file);
                $file=str_replace('#vnet#',$vnet,$file);
                $file=str_replace('#applytpl#',$applytpl,$file);
                $file=str_replace('#mdsize#',$mdsize,$file);
                $file=str_replace('#floatresolv#',$floatresolv,$file);

                $file=str_replace('#pkg_bootstrap#',$pkg_bootstrap,$file);
                $file=str_replace('#user_pw_root#',$user_pw_root,$file);
                $file=str_replace('#interface#',$interface,$file);
                $file=str_replace('#sysrc_enable#',$sysrc_enable,$file);

                $file=str_replace('#runasap#',$runasap,$file);
                $file=str_replace('#with_img_helpers#',$with_img_helpers,$file);
        }
        return $file;
}

function forms( $dbfilepath, $helper )
{
	$db = new SQLite3($dbfilepath); $db->busyTimeout(5000);

	$query="SELECT idx,group_id,order_id,param,desc,def,cur,new,mandatory,attr,xattr,type FROM forms ORDER BY group_id ASC, order_id ASC";

	$fields = $db->query($query);

	$res=cmd(CBSD_CMD.'freejname');

	if ($res['retval'] != 0 ) {
		if (!empty($res['error_message']))
		echo $res['error_message'];
		exit(1);
	}

	$freejname=$res['message'];
	?>

	<form class="helperbox" name="<?php echo $helper; ?>" id="<?php echo $helper; ?>" action="helper.php?helper=<?php echo $helper; ?>" method="POST">

	<label for="jname">jname</label>
	<input type="text" name="newjname" required value="<?php echo $freejname; ?>"/><br>

	<label for="ip4_addr">ip4_addr</label>
	<input type="text" name="ip4_addr" required value="DHCP" /><br>

	<?php

	while ($row = $fields->fetchArray()) {

		list( $idx , $group_id, $order_id , $param , $desc , $def , $cur , $new , $mandatory , $attr , $xattr , $type ) = $row;

		$tpl=getElement($type);

		$params=array('param','desc','attr','cur');

		$tpl=str_replace('${param}',$param,$tpl);

		foreach($params as $param)
		{
			if($param) {
//				$tpl=str_replace('${'.$param.'}',$param,$tpl);
				$tpl=str_replace('${'.$param.'}',$$param,$tpl);
			}
		}

		$value=$def;
		if(isset($cur) && !empty($cur)) $value=$cur;
		$tpl=str_replace('${def}',$value,$tpl);

		$required=($mandatory==1)?' required':'';
		$tpl=str_replace('${required}',$required,$tpl);
		echo $tpl;
	}

	?>
	<input type="submit" value="Apply"/>
	<br>
	</form>
	<?php
}

function getElement($el)
{
	$tpl='';

	switch($el)
	{
		case 'inputbox':
			$tpl .= '"${desc}" : <input type="text" name="${param}" value="${def}" ${attr}${required} /><br>';
			break;
		case 'delimer':
			$tpl .= '<h1>${desc}</h1>';
			break;
	}
	return $tpl;
}


function setButtons($arr=array())
{
	echo '<div class="buttons"><input type="button" value="Apply" /> <input type="button" value="Cancel" /></div>';
}


if (!isset($_GET['helper'])) {
        echo "Empty helper";
        exit(0);
} else {
        $helper=$_GET['helper'];
}

$jail_form=$workdir."/formfile/".$helper.".sqlite";

if (!file_exists($jail_form)) {
	$res=cmd(CBSD_CMD."forms module=$helper inter=0");
	if (!file_exists($jail_form)) {
		echo "No such module $helper at $jail_form";
		die();
	}
}

if (isset($_POST['newjname'])) {
	$newjname = $_POST['newjname'];

	$db = new SQLite3($jail_form); $db->busyTimeout(5000);
	$query="SELECT param FROM forms";
	$fields = $db->query($query);

	$tmpfname = tempnam("/tmp", "HLPR");
	copy($jail_form, $tmpfname);

	while ($row = $fields->fetchArray()) {
		list( $param ) = $row;

		$dbnew = new SQLite3($tmpfname); $dbnew->busyTimeout(5000);

		if (isset($_POST["$param"])) {
			$val=$_POST["$param"];
//			$update = "UPDATE forms SET new = '$val' WHERE param = '$param'";
//			echo "UPDATE forms SET new='{$val}' WHERE param='{$param}'";
			$dbnew->exec("UPDATE forms SET new='{$val}' WHERE param='{$param}'");
		}

		$dbnew->close();
//		echo $param;
	}

	echo "tMP: $tmpfname";

	$jname=$newjname;

	$with_img_helpers="$tmpfname";
	$runasap="1";

	$path="$workdir/jails/$jname";
	$host_hostname="$jname.my.domain";
	$ip4_addr="DHCP";
	$mount_devfs="1";
	$allow_mount="1";
	$allow_devfs="1";
	$allow_nullfs="1";
	$mount_fstab="$workdir/jails-fstab/fstab.$jname";
	$mkhostsfile="1";
	$devfs_ruleset="4";
	$ver="native";
	$baserw="0";
	$mount_src="0";
	$mount_obj="0";
	$mount_kernel="0";
	$mount_ports="1";
	$astart="1";
	$data="$workdir/jails-data/$jname-data";
	$vnet="0";
	$applytpl="1";
	$mdsize="0";
	$rcconf="$workdir/jails-rcconf/rc.conf_$jname";
	$floatresolv="1";
	$pkg_bootstrap="1";
	$user_pw_root='';
	$interface="auto";
	$jailskeldir="$workdir/share/FreeBSD-jail-skel";
	$sysrc_enable="";

$userappend="";

if (isset($user_add)) {
 $userappend = <<<EOF

user_add='$user_add'
user_pw_${user_add}='$user_add_password'
user_gecos_${user_add}='$user_add_gecos'
user_home_${user_add}='/home/$user_add'
user_shell_${user_add}='/bin/csh'
user_member_groups_${user_add}='wheel'

EOF;
}


	$tpl=xgetJailTemplate();
	$file_name='/tmp/'.$newjname.'.jconf';
	file_put_contents($file_name,$tpl.$userappend);
	$res=cmd(CBSD_CMD."task owner=cbsdweb mode=new /usr/local/bin/cbsd jcreate inter=0 jconf=/tmp/$newjname.jconf");
	//header( 'Location: /index.php?mod=jailscontainers' ) ;
	
	exit(0);
}

$jail_form=$workdir."/formfile/".$helper.".sqlite";
forms( $jail_form, $helper );
