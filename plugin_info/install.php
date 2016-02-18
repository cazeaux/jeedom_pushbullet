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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';


function pushbullet_install() {
    $cron = cron::byClassAndFunction('pushbullet', 'pull');
	if (is_object($cron)) {
        $cron->remove();
    }
/*    if (!is_object($cron)) {
        $cron = new cron();
        $cron->setClass('pushbullet');
        $cron->setFunction('pull');
        $cron->setEnable(1);
        $cron->setDeamon(0);
        $cron->setSchedule('1-59/2 * * * *');
        $cron->save();
    }*/
       
}

function pushbullet_update() {  
    foreach (eqLogic::byType('pushbullet') as $pushbullet) {
        if (!$pushbullet->getConfiguration('listenAllPushes')) {
            $pushbullet->setConfiguration('listenAllPushes', 0);
        }
    }
	
    if (method_exists('pushbullet', 'stopAllDeamon')) {
        pushbullet::stopAllDeamon();
    }

}

function pushbullet_remove() {
    $cron = cron::byClassAndFunction('pushbullet', 'pull');
    if (is_object($cron)) {
        $cron->remove();
    }

    if (method_exists('pushbullet', 'stopAllDeamon')) {
        pushbullet::stopAllDeamon();
    }
}
?>