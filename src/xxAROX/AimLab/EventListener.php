<?php
declare(strict_types=1);
namespace xxAROX\AimLab;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use xxAROX\AimLab\player\AimLabSession;


/**
 * Class EventListener
 * @package xxAROX\AimLab
 * @author Jan Sohn / xxAROX
 * @date 24. March, 2023 - 20:41
 * @ide PhpStorm
 * @project Aim-Lab
 */
class EventListener implements Listener{
	function PlayerJoinEvent(PlayerJoinEvent $event): void{
		AimLabPlugin::getInstance()->sessions->offsetSet($event->getPlayer(), $session=new AimLabSession($event->getPlayer()));
	}
	function PlayerQuitEvent(PlayerQuitEvent $event): void{
		if (AimLabPlugin::getInstance()->sessions->offsetExists($event->getPlayer())) {
			$session = AimLabPlugin::getInstance()->sessions->offsetGet($event->getPlayer());
			if ($session instanceof AimLabSession) $session->getSettings()->save();
			AimLabPlugin::getInstance()->sessions->offsetUnset($event->getPlayer());
		}
	}
}
