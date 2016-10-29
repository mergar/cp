<?php
function fetch_instances($dbfilepath)
{
	global $workdir;
	global $rp;

	$res=cmd(CBSD_CMD.'forms header=0');

	if ($res['retval'] != 0 ) {
		if (!empty($res['error_message']))
			echo $res['error_message'];
			exit(1);
	}

	$lst=explode("\n",$res['message']);
	$n=0;

	$str="";

	if(!empty($lst)) foreach($lst as $item) {

	$dbfilepath=$workdir."/formfile/".$item.".sqlite";

	$longdesc="unable to fetch desc";

	if (file_exists($dbfilepath)) {

		$db = new SQLite3($dbfilepath); $db->busyTimeout(5000);
		$helpers = $db->query('SELECT longdesc FROM system');

		if (!($helpers instanceof Sqlite3Result)) {
			echo "Error: $dbfilepath";
		} else {
			while ($row = $helpers->fetchArray()) {
				list( $longdesc ) = $row;
			}
			$db->close();
		}
	}

	if (file_exists($rp."/img/logo/$item.svg")) {
		$imgsrc="/img/logo/$item.svg";
	} else {
		$imgsrc="/img/logo/empty.svg";
	}


	$str .= <<<EOF
 <tr>
 <td>
        <a href="/instance_jail/helper.php?helper=$item"><img src="$imgsrc" width="200" height="100" alt="$item.svg"></a>
 </td>
 <td>
        <a href="/instance_jail/helper.php?helper=$item">$item</a>
 </td>
 <td>
        $longdesc
 </td>
 </tr>
EOF;
}
	echo $str;

}


function show_instances()
{
	global $workdir;

	fetch_instances($workdir."/var/db/storage_media.sqlite");
}
?>
