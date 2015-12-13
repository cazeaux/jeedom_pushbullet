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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

define('PUSHBULLETURL', 'https://api.pushbullet.com/v2/pushes');
define('PUSHBULLETURLDEVICES', 'https://api.pushbullet.com/v2/devices');
define('PUSBULLET_COMMAND_RAPPEL_1', 'rappel');
define('PUSBULLET_COMMAND_RAPPEL_2', 'p');
define('PUSHBULLETME', 'https://api.pushbullet.com/v2/users/me');


class pushbullet extends eqLogic {

	public function mylog($txt) {/*
		$fp = fopen('/tmp/log', 'a+');
		fputs($fp, $txt."\n");
		fclose($fp);*/
	}


    public static function pull($_options) {
        foreach (eqLogic::byType('pushbullet') as $pushbullet) {
			if (is_object($pushbullet) && $pushbullet->getConfiguration('isPushEnabled')) {
				$pushbullet->myLog('PUSH');
				$pushbullet->checkLastPush();
			}
		}
    }

	public static function activateReminder($_options) {
		$pushbullet = pushbullet::byId($_options['pushbullet_id']);
//		$pushbullet->myLog('reminder '.serialize($_options));
        if (is_object($pushbullet)) {
			foreach ($pushbullet->getCmd() as $cmd) {
				if($cmd->getConfiguration('isPushChannel')) {
//					$pushbullet->myLog('send event reminder '.$_options['body']);
					$cmd->event($_options['body']);
					$pushbullet->setLastValue($_options['body']);
					// Lancement des interactions
					if ($pushbullet->getConfiguration('isInteractionEnabled'))
					{
						$reply = interactQuery::tryToReply(trim($_options['body']), array());
						if (trim($reply) != '') {
							$messageBody = '';
							if (!$pushbullet->getConfiguration('dismissInitialCommandeInReply')) {
								$messageBody = 'Commande initiale : '.$_options['body'];
							}
							
							foreach ($pushbullet->getCmd() as $cmdResponse) {
								if($cmdResponse->getConfiguration('isResponseDevice')) {
									$cmdResponse->execute(array('title' => $reply, 'message' => $messageBody));
								}
							}
						}
					}
				}
			}
		}
	}
	
	public function checkLastPush() {
		$eventBodyToSend = "";
		foreach ($this->getCmd() as $cmd) {
			if($cmd->getConfiguration('isPushChannel')) {
				$events = $this->getLastPush($cmd->getConfiguration('deviceid'));
				$this->myLog('new event '.serialize($events));
				foreach ($events as $event) {
					if ($event["body"])	{
						// vérification des commandes avancés
						$sendEvent = true;
						
						$lines = explode("\n", $event['body']);
						if (count($lines) > 1) {
							$eventBodies = array_slice($lines, 1);
							$eventProgDate = $lines[0];
						}
						else {
							$eventBody = $lines[0];
							$eventProgDate = "";
						}
						$this->myLog('new event '.serialize($eventBody));
						
						
						
						if ($eventProgDate && preg_match('/([a-zA-Z]+)\s(.*)$/', strtolower($eventProgDate), $matches)) {
							$command = strtolower($matches[1]);
							$this->myLog('commande '.$matches[1].'/'.$matches[2]);
							
							// PUSH REMINDER
							if ($command == PUSBULLET_COMMAND_RAPPEL_1 || $command == PUSBULLET_COMMAND_RAPPEL_2) {
								$timestamp = strtotime($matches[2]);
								if ($timestamp && $timestamp - date() > 60) {
									foreach ($eventBodies as $eventBody) {
										$arrayCronOptions = array('pushbullet_id' => intval($this->getId()), 'cron_id' => time(), 'body' => $eventBody, 'source' => $event['source']);
										$cron = cron::byClassAndFunction('pushbullet', 'activateReminder', $arrayCronOptions);

										if (!is_object($cron)) {
											$cron = new cron();
											$cron->setClass('pushbullet');
											$cron->setFunction('activateReminder');
											$cron->setOption($arrayCronOptions);
											$cron->setOnce(1);
										}

										$cronDate = date('i', $timestamp) . ' ' . date('H', $timestamp) . ' ' . date('d', $timestamp) . ' ' . date('m', $timestamp) . ' * ' . date('Y', $timestamp);
										$this->myLog('crontDate '.$cronDate);

										$cron->setSchedule($cronDate);
										$cron->save();
									}
									$sendEvent = false;
								} else {
									$this->myLog('Reminder failed ');
									$eventBody = 'Reminder failed';
								}
							}
						}
						
						if ($sendEvent) {
							$this->myLog('Send Event : '.$eventBody);
							$eventBodyToSend = $eventBody;
							/*
							MODIFICATION TEMPORAIRE : on sauvegarde l'eventBody à déclencher. Du coup, seul le dernier sera pris en compte
							$this->myLog('Send normal event');
							$cmd->event($eventBody);
							$this->setLastValue($eventBody);
							*/
							// Lancement des interactions
							if ($this->getConfiguration('isInteractionEnabled'))
							{
								$reply = interactQuery::tryToReply(trim($eventBody), array());
								$this->myLog('Interaction reply : '.$reply);
								if (trim($reply) != '') {
									$messageBody = '';
									if (!$this->getConfiguration('dismissInitialCommandeInReply')) {
										$messageBody = 'Commande initiale : '.$eventBody;
									}
									
									foreach ($this->getCmd() as $cmd_response) {
										// 3 cas possibles ici, dans l'ordre:
										// 1: pas d'envoi de la réponse à la source de la commande, donc on envoie aux devices sélectionnés
										// 2: envoi de la réponse à la source de la commande, et on trouve le device source
										// 3: envoi de la réponse à la source de la commande, on ne trouve pas le device source donc on envoie aux devices sélectionnés
										
										if(    (!$this->getConfiguration('sendBackReponseToSource') && $cmd_response->getConfiguration('isResponseDevice'))
											|| ($this->getConfiguration('sendBackReponseToSource') && $cmd_response->getConfiguration('deviceid') == $event['source'])
											|| ($this->getConfiguration('sendBackReponseToSource') && !$event['source'] && $cmd_response->getConfiguration('isResponseDevice'))) {
								$this->myLog('Send reply : '.$cmd_response->getConfiguration('deviceid'));
											$cmd_response->execute(array('title' => $reply, 'message' => $messageBody));
										}
									}
								}
							}
						}
					}
				}
				
				if ($eventBodyToSend) {
					$cmd->event($eventBodyToSend);
					$this->setLastValue($eventBodyToSend);
				}
			}
		}
	}

	public function getLastTimestamp() {
		return $this->getConfiguration('timestamp');
	}
	
	public function setLastTimestamp($timestamp) {
		$this->setConfiguration('timestamp', $timestamp+1);
		$this->save();
	}

	public function getLastValue() {
		return $this->getConfiguration('lastvalue');
	}
	
	public function setLastValue($lastvalue) {
		$this->setConfiguration('lastvalue', $lastvalue);
		$this->save();
	}

	public function getLastPush($pushdeviceid) {
		// sendRequest 
		$return = array();
		$curl = curl_init();
		
		// Servira a setter le dernier timestamp sur le dernier push de la liste
		$bIsFirstPush = true;

		$timestamp = $this->getLastTimestamp();
		if (!$timestamp) $timestamp = 0;
		curl_setopt($curl, CURLOPT_URL, PUSHBULLETURL.'?modified_after='.$timestamp);
		curl_setopt($curl, CURLOPT_HTTPGET, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_USERPWD, $this->getConfiguration('token') . ":");
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		
		
		$curlData = curl_exec($curl);
		if ($curlData) {
			$jsonData = json_decode($curlData, true);
			$this->myLog($curlData);
			foreach ($jsonData['pushes'] as $push)
			{
				$this->myLog($push['body']);
				if ($push['active'] && $push['target_device_iden'] == $pushdeviceid) {
					$this->myLog('new push');
					$return[] = array('timestamp' => $push['modified'], 'body' => $push['body'], 'title' => $push['title'], 'source' => $push['source_device_iden']);
				}
				if ($bIsFirstPush) {
					// Premier push de la liste, donc on en sauvegarde le timestamp pour la prochaine fois
					$this->setLastTimestamp($push['modified']);
					$bIsFirstPush = false;
				}
			}
		}
		else {
			throw new Exception(__('Erreur Pushbullet Cron : '.curl_error($curl), __FILE__));
		}
		curl_close($curl);

		return array_reverse($return);
			
	}


    public function preUpdate() {
		$bIsPushEnabled = $this->getConfiguration('isPushEnabled');
		$jeedomDeviceName = $this->getConfiguration('jeedomDeviceName');
		$currentId = $this->getId();

		$this->setCategory('Communication', 1);

        if ($this->getConfiguration('token') == '') {
            throw new Exception(__('Le Token ne peut être vide', __FILE__));
        }
		else if (! $this->testToken($this->getConfiguration('token'))) {
            throw new Exception(__('Erreur Pushbullet : Le Token fourni est invalide', __FILE__));
		}
		else {
			
			// On récupère les commandes déjà créées, dans le cas d'un UPDATE. Vide s'il s'agit d'une première création
			foreach ($this->getCmd() as $cmd) {
				if (!$bIsPushEnabled && $cmd->getConfiguration('isPushChannel')) {
					$cmd->remove();
				}
			}
			
			// On verifie si le nom de device pusbullet n'est pas deja utilise
			if ($bIsPushEnabled) {
				foreach (eqLogic::byType('pushbullet') as $pushbullet) {
					if ($pushbullet->getId() != $currentId) {
						foreach ($pushbullet->getCmd() as $cmd) {
							if ($cmd->getConfiguration('isPushChannel') && $cmd->getName() == $jeedomDeviceName) {
								throw new Exception(__('Erreur Pushbullet : Nom de device "'.$jeedomDeviceName.'" déjà utilisé', __FILE__));
							}
						}
					}
				}
			}
		}
    }
	

	public function postUpdate() {
 		$this->mylog('POST UPDATE');
		$arrayExistingCmd = array();
		$jeedomDeviceExitsAtPushbullet = 0;
		$bCreatePushDevice = true;
		$bIsPushEnabled = $this->getConfiguration('isPushEnabled');
		$jeedomDeviceName = $this->getConfiguration('jeedomDeviceName');
		$this->myLog('name : '.$jeedomDeviceName);
		$this->myLog('id : '.$jeedomDeviceId);
		
		if (!$jeedomDeviceName) {
			$jeedomDeviceName = 'jeedom_'.$this->id;
		}
	
		
		// On récupère les commandes déjà créées, dans le cas d'un UPDATE. Vide s'il s'agit d'une première création
		$arrayEquipmentCmd = $this->getCmd();
		foreach ($arrayEquipmentCmd as $cmd) {
			if ($cmd->GetConfiguration('isPushChannel')) {
				$jeedomDeviceId = $cmd->getConfiguration('deviceid');
				if ($cmd->getName() != $jeedomDeviceName) {
					$cmd->remove();
				}
				else {
					$arrayExistingCmd[$jeedomDeviceId] = 1;
				}
			}
			else {
				$arrayExistingCmd[$cmd->GetConfiguration('deviceid')] = 1;
			}
		}
		
		// A ce stade on a le jeedomDeviceName (ou celui par défaut si non défini) et le jeedomDeviceId s'il est déjà connu

		// On récupère les devices créés sur PUSHBULLET
		$arrayDevices = $this->GetDevices();
		
		// Pour stocker les ID de tous les devices SAUF le device de push
		$arrayAllDevices = array();
		
		// on crée les commandes des devices PUSHBULLET si elles n'existent pas encore
		foreach ($arrayDevices as $deviceEntry) {
			$this->mylog('loop '.$deviceEntry["deviceid"]);
			$this->mylog('loop '.$deviceEntry["name"]);

			if ($deviceEntry["deviceid"] != $jeedomDeviceId) {
				$arrayAllDevices[] = $deviceEntry["deviceid"];
			}
			
			// Device normaux (type action)
			if (!isset($arrayExistingCmd[$deviceEntry["deviceid"]]) && $deviceEntry["deviceid"] != $jeedomDeviceId) {
				// On marque le device comme étant valide.

				$device = new pushbulletCmd();
				$device->setName(__($deviceEntry["name"], __FILE__));
				$device->setEqLogic_id($this->id);
				$device->setConfiguration('deviceid', $deviceEntry["deviceid"]);
				$device->setUnite('');
				$device->setType('action');
				$device->setSubType('message');
				$device->setIsHistorized(0);
				$device->save();
			}
			// Device spécifique pour le push (type info)
			else if ($deviceEntry["deviceid"] == $jeedomDeviceId) {

				$jeedomDeviceExitsAtPushbullet = $deviceEntry;
				
				if (isset($arrayExistingCmd[$deviceEntry["deviceid"]])) {
					// Le device push existe déjà, on ne le recrée pas
					$bCreatePushDevice = false;

					// si push désactivé, on supprime le device
				}
				
			}
			
			$arrayExistingCmd[$deviceEntry["deviceid"]] = -1;
			
		}

		// Création du device PUSH s'il n'existe pas encore
		if ($bCreatePushDevice && $bIsPushEnabled) {
			if (!$jeedomDeviceExitsAtPushbullet) {
				$jeedomDevice = $this->createJeedomDevice($jeedomDeviceName);
				$jeedomDeviceId = $jeedomDevice["deviceid"];
				$deviceTimestamp = $jeedomDevice["timestamp"];
			}
			else {
				$jeedomDeviceId = $jeedomDeviceExitsAtPushbullet["deviceid"];
				$deviceTimestamp = 0;
				
			}
			
			$device = new pushbulletCmd();
			$device->setName(__($jeedomDeviceName, __FILE__));
			$device->setEqLogic_id($this->id);
			$device->setConfiguration('deviceid', $jeedomDeviceId);
			$device->setConfiguration('isPushChannel', '1');
			$device->setUnite('');
			$device->setType('info');
			$device->setSubType('string');
			$device->setIsHistorized(0);
			$device->save();

			//$this->setLastTimestamp($deviceTimestamp);
			
		}
		else if ($bIsPushEnabled && $jeedomDeviceId) {
			$this->updateJeedomDevice($jeedomDeviceId, $jeedomDeviceName);
		}

		
		
		// device 'ALL'
		if (!isset($arrayExistingCmd['all'])) {
			$device = new pushbulletCmd();
			$device->setName(__('Tous les devices', __FILE__));
			$device->setEqLogic_id($this->id);
			$device->setConfiguration('deviceid', 'all');
			$device->setConfiguration('pushdeviceids', implode(",", $arrayAllDevices));
			$device->setUnite('');
			$device->setType('action');
			$device->setSubType('message');
			$device->setIsHistorized(0);
			$device->save();
		}
		
		// On supprime les commandes en trop et on update le device ALL
		foreach ($arrayEquipmentCmd as $cmd) {
			if ($arrayExistingCmd[$cmd->GetConfiguration('deviceid')] == 1 && $cmd->GetConfiguration('deviceid') != 'all' && !$cmd->GetConfiguration('isPushChannel')) {
				$cmd->remove();
			}
			else if ($cmd->getConfiguration('deviceid') == 'all') {
				$cmd->setConfiguration('pushdeviceids', implode(",", $arrayAllDevices));
				$cmd->save();
			}
		}
	}
	
    public function getDevices() {
		// sendRequest 
		$return = array();
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, PUSHBULLETURLDEVICES);
		curl_setopt($curl, CURLOPT_HTTPGET, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_USERPWD, $this->getConfiguration('token') . ":");
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		
		$curlData = json_decode(curl_exec($curl), true);
		if ($curlData) {
			foreach ($curlData["devices"] as $device) {
				if ($device['active'] == 'true') {
					$return[] = array("name" => $device["nickname"], "deviceid" => $device["iden"]);
				}
			}
		}
		else {
			throw new Exception(__('Erreur Pushbullet GetDevices : '.curl_error($curl), __FILE__));
		}
		curl_close($curl);
		return $return;
    }

    public function preRemove() {
		$this->mylog('REMOVE');

		// recherche et suppression du device jeedom 
		foreach ($this->getCmd() as $cmd) {
			$this->mylog('loop '.$cmd->getName());
			if ($cmd->getConfiguration('isPushChannel') == 1) {
				$this->removeJeedomDevice($cmd->getConfiguration('deviceid'));
			}
		}
		return true;
    }
	
    public function createJeedomDevice($jeedomDeviceName) {
		
		$arrayData = array('nickname' => $jeedomDeviceName, 'type' => 'stream');
		
		// sendRequest 
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, PUSHBULLETURLDEVICES);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_USERPWD, $this->getConfiguration('token') . ":");
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $arrayData);
		
		$curlData = json_decode(curl_exec($curl), true);
		if ($curlData) {
			$return = array("deviceid" => $curlData["iden"], "timestamp" => $curlData["modified"]);
		}
		else {
			$return = array();
			throw new Exception(__('Erreur Pushbullet CreateJeedomDevice : '.curl_error($curl), __FILE__));
		}
		curl_close($curl);
		
		return $return;
    }

	public function updateJeedomDevice($deviceId, $jeedomDeviceName) {
		
		$arrayData = array('nickname' => $jeedomDeviceName);
		
		// sendRequest 
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, PUSHBULLETURLDEVICES.'/'.$deviceId);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_USERPWD, $this->getConfiguration('token') . ":");
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $arrayData);
		
		$curlData = json_decode(curl_exec($curl), true);
		if ($curlData) {
			$return = array("deviceid" => $curlData["iden"], "timestamp" => $curlData["modified"]);
		}
		else {
			$return = array();
			throw new Exception(__('Erreur Pushbullet UpdateJeedomDevice : '.curl_error($curl), __FILE__));
		}
		curl_close($curl);
		
		return $return;
    }

    public function removeJeedomDevice($jeedomDeviceId) {
		
		// sendRequest 
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, PUSHBULLETURLDEVICES.'/'.$jeedomDeviceId);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_USERPWD, $this->getConfiguration('token') . ":");
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_exec($curl);
		curl_close($curl);
		
    }
	
	function getJeedomDeviceId() {
		foreach ($this->getCmd() as $cmd) {
			if ($cmd->getConfiguration('isPushChannel') == 1) {
				$jeedomDeviceId = $cmd->getConfiguration('deviceid');
			}
		}
		return $jeedomDeviceId;
	}
	
    public function testToken($token) {
		
		// sendRequest 
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, PUSHBULLETME);
		curl_setopt($curl, CURLOPT_HTTPGET, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_USERPWD, $token . ":");
		curl_setopt($curl, CURLOPT_FAILONERROR, true);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		$curlData = curl_exec($curl);
		if (curl_errno($curl)) {
			curl_close($curl);
			return false;
		}
		curl_close($curl);
		return true;
    }
	
    public function getShowOnChild() {
        return true;
    }

	
    public static function cron() {
        foreach (eqLogic::byType('pushbullet') as $pushbullet) {
			if (is_object($pushbullet) && $pushbullet->getConfiguration('isPushEnabled')) {
				if (!$pushbullet->deamonRunning()) {
					$pushbullet->runDeamon();
				}
			}
			else if (is_object($pushbullet) && !$pushbullet->getConfiguration('isPushEnabled')) {
				if ($pushbullet->deamonRunning()) {
					$pushbullet->stopDeamon();
				}
			}
		}
    }


    public function runDeamon() {
        $daemon_path = realpath(dirname(__FILE__) . '/../../ressources/pushbullet_daemon');

        $cmd = 'nice -n 19 /usr/bin/python ' . $daemon_path . '/pushbullet.py '.$this->getConfiguration('token');

        $result = exec('nohup ' . $cmd . ' > /dev/null 2>&1 &');
        if (strpos(strtolower($result), 'error') !== false || strpos(strtolower($result), 'traceback') !== false) {
            log::add('sms', 'error', $result);
            return false;
        }

        sleep(2);
        if (!$this->deamonRunning()) {
            sleep(10);
            if (!$this->deamonRunning()) {
                return false;
            }
        }
    }

    public function deamonRunning() {
        $pid_file = realpath(dirname(__FILE__) . '/../../../../tmp/pushbullet.'.$this->getConfiguration('token').'.pid');
        if (!file_exists($pid_file)) {
            $pid = jeedom::retrievePidThread('pushbullet.py');
            if ($pid != '' && is_numeric($pid)) {
                exec('kill -9 ' . $pid);
            }
            return false;
        }
        $pid = trim(file_get_contents($pid_file));
        if ($pid == '' || !is_numeric($pid)) {
            $pid = jeedom::retrievePidThread('pushbullet.py');
            if ($pid != '' && is_numeric($pid)) {
                exec('kill -9 ' . $pid);
            }
            return false;
        }
        $result = exec('cat /proc/' . $pid . '/cmdline | grep "pushbullet" | wc -l', $output, $retcode);
        if (($retcode == 0 && $result == 0 && file_exists($pid_file) ) || $retcode != 0) {
            unlink($pid_file);
            return false;
        }
        return true;
    }

    public function stopDeamon() {
        if (!$this->deamonRunning()) {
            return true;
        }
        $pid_file = dirname(__FILE__) . '/../../../../tmp/pushbullet.'.$this->getConfiguration('token').'.pid';
        if (!file_exists($pid_file)) {
            return true;
        }
        $pid = intval(file_get_contents($pid_file));
        $kill = posix_kill($pid, 15);
        $retry = 0;
        while (!$kill && $retry < 10) {
            $kill = posix_kill($pid, 9);
            $retry++;
        }
        return !$kill;
    }

	public static function stopAllDeamon() {
        foreach (eqLogic::byType('pushbullet') as $pushbullet) {
			$pushbullet->stopDeamon();
		}
    }

  
}

class pushbulletCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */
    public function execute($_options = null) {
        $eqLogic_pushbullet = $this->getEqLogic();
		
		if ($this->getConfiguration('isPushChannel') == 1) {
			return $eqLogic_pushbullet->getLastValue();
		}
		else {
			if ($_options === null) {
				throw new Exception(__('Les options de la fonction ne peuvent etre null', __FILE__));
			}
			if ($_options['message'] == '' && $_options['title'] == '') {
				throw new Exception(__('Le message et le sujet ne peuvent être vide', __FILE__));
			}
			/*
			Depuis la 2.12, on retire le titre par defaut
			if ($_options['title'] == '') {
				$_options['title'] = __('[Jeedom] - Notification', __FILE__);
			}
			*/
			// prepare data
			$arrayData = array("type" => "note", "title" => $_options['title'], "body" => $_options['message']);
			$jeedomDeviceId = $eqLogic_pushbullet->getJeedomDeviceId();
			if ($jeedomDeviceId) {
				$arrayData["source_device_iden"] = $jeedomDeviceId;
			}


			// sendRequest 
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, PUSHBULLETURL);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_USERPWD, $eqLogic_pushbullet->getConfiguration('token') . ":");
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			
			if ($this->getConfiguration('deviceid') != 'all') {
					$arrayData["device_iden"] = $this->getConfiguration('deviceid');
			}
			curl_setopt($curl, CURLOPT_POSTFIELDS, $arrayData);
			curl_exec($curl);
			
			curl_close($curl);
		}
    }
	
	public function preRemove() {
        $eqLogic_pushbullet = $this->getEqLogic();
		if ($this->getConfiguration('isPushChannel') == 1) {
			$eqLogic_pushbullet->removeJeedomDevice($this->getConfiguration('deviceid'));
		}
	}

    /*     * **********************Getteur Setteur*************************** */
}

?>
