<?php /* $Id: $ */

// returns a associative arrays with keys 'destination' and 'description'
function miscdests_destinations() {
	$results = miscdests_list();

	// return an associative array with destination and description
	if (isset($results)) {
		foreach($results as $result){
				$extens[] = array('destination' => 'ext-miscdests,'.$result['0'].',1', 'description' => $result['1']);
		}
		return $extens;
	} else {
		return null;
	}
}

function miscdests_getdest($exten) {
	return array('ext-miscdests,'.$exten.',1');
}

function miscdests_getdestinfo($dest) {
	global $active_modules;

	if (substr(trim($dest),0,14) == 'ext-miscdests,') {
		$exten = explode(',',$dest);
		$exten = $exten[1];
		$thisexten = miscdests_get($exten);
		if (empty($thisexten)) {
			return array();
		} else {
			//$type = isset($active_modules['announcement']['type'])?$active_modules['announcement']['type']:'setup';
			return array('description' => 'Misc Destination : '.$thisexten['description'],
			             'edit_url' => 'config.php?display=miscdests&id='.urlencode($exten),
								  );
		}
	} else {
		return false;
	}
}

/* 	Generates dialplan for conferences
	We call this with retrieve_conf
*/
function miscdests_get_config($engine) {
	global $ext;  // is this the best way to pass this?

	switch($engine) {
		case "asterisk":
			$contextname = 'ext-miscdests';
			$fctemplate = '/\{(.+)\:(.+)\}/';
			
			if(is_array($destlist = miscdests_list())) {
				
				foreach($destlist as $item) {
					$miscdest = miscdests_get($item['0']);
					
					$miscid = $miscdest['id'];
					$miscdescription = $miscdest['description'];
					$miscdialdest = $miscdest['destdial'];

					// exchange {mod:fc} for the relevent feature codes in $miscdialdest
					$miscdialdest = preg_replace_callback($fctemplate, "miscdests_lookupfc", $miscdialdest);

					// write out the dialplan details
					$ext->add($contextname, $miscid, '', new ext_noop('MiscDest: '.$miscdescription));
					$ext->add($contextname, $miscid, '', new ext_goto('from-internal,'.$miscdialdest.',1', ''));
					
				}
			}

		break;
	}
}

function miscdests_list() {
	$results = sql("SELECT id, description FROM miscdests ORDER BY description","getAll",DB_FETCHMODE_ASSOC);
	foreach($results as $result){
		$extens[] = array($result['id'],$result['description']);
	}

	if (isset($extens)) {
		return $extens;
	} else {
		return null;
	}
}

function miscdests_get($id){
	$results = sql("SELECT id, description, destdial FROM miscdests WHERE id = $id","getRow",DB_FETCHMODE_ASSOC);
	return $results;
}

function miscdests_del($id){
	$results = sql("DELETE FROM miscdests WHERE id = $id","query");
}

function miscdests_add($description, $destdial){
	$results = sql("INSERT INTO miscdests (description, destdial) VALUES (".sql_formattext($description).",".sql_formattext($destdial).")");
}

function miscdests_update($id, $description, $destdial){
	$results = sql("UPDATE miscdests SET description = ".sql_formattext($description).", destdial = ".sql_formattext($destdial)." WHERE id = ".$id);
}

function miscdests_lookupfc($matches) {
	$modulename = $matches[1];
	$featurename = $matches[2];

	$fcc = new featurecode($modulename, $featurename);
	$fc = $fcc->getCodeActive();
	return $fc;
}
?>
