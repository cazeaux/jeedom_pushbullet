<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */
require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";
include_file('core', 'pushbullet', 'class', 'pushbullet');

if (php_sapi_name() != 'cli' || isset($_SERVER['REQUEST_METHOD']) || !isset($_SERVER['argc'])) {
    if (config::byKey('api') != init('apikey') && init('apikey') != '') {
        connection::failed();
        echo 'Clef API non valide, vous n\'etes pas autorisé à effectuer cette action (jeeZwave)';
        die();
    }
}

if (isset($argv)) {
	$pushbulletKey = $argv[1];
}
else {
	die();
}

$eqLogics = eqLogic::byType('pushbullet');
if (count($eqLogics) < 1) {
    die();
}
$fp = fopen("/tmp/pushbullet", "c+");
foreach ($eqLogics as $eqLogic) {
	if ($eqLogic->getConfiguration('token') == $pushbulletKey) {
		if (is_object($eqLogic) && $eqLogic->getConfiguration('isPushEnabled')) {
		//	$eqLogic2 = new pushbullet();
		//	$eqLogic2 = cast($eqLogic, $eqLogic2);
			$eqLogic->checkLastPush();
			break;
        }
    }
}
fclose($fp);


	

?>