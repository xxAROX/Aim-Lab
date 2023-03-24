<?php
declare(strict_types=1);
namespace xxAROX\AimLab\player;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\Filesystem;
use xxAROX\AimLab\AimLabPlugin;
use xxAROX\AimLab\entity\AimEntity;
use xxAROX\AimLab\items\LeaveItem;
use xxAROX\AimLab\items\PlayItem;
use xxAROX\AimLab\items\SettingsItem;


/**
 * Class AimLabSession
 * @package xxAROX\AimLab\player
 * @author Jan Sohn / xxAROX
 * @date 24. March, 2023 - 20:42
 * @ide PhpStorm
 * @project Aim-Lab
 */
final class AimLabSession{
	protected Config $config;
	protected AimLabSettings $settings;
	public array $aim_entities = [];

	protected bool $in_lobby = true;

	/**
	 * AimLabSession constructor.
	 * @param Player $player
	 */
	public function __construct(protected Player $player){
		$this->config = new Config(AimLabPlugin::getInstance()->getDataFolder() . "players/" . (empty($player->getXuid()) ? $player->getName() : $player->getXuid()) . ".json");
		$this->settings = new AimLabSettings($this);
		$this->giveItems();
	}

	public function tick(): void{
		if (Server::getInstance()->getTick() %20 == 0) {
			$v = $this->newRandomVec();
			$aim = new AimEntity(new Location($v->x, $v->y, $v->z, $this->player->getWorld(), 0, 0));
			$aim->setSession($this);
			$aim->spawnTo($this->player);
			$this->aim_entities[$aim->getId()] = $aim;
		}
	}

	private function newRandomVec(): Vector3{
		return $this->player->getDirectionVector()->add(mt_rand(-100, 100) /10000, 1, mt_rand(-100, 100) /10000);
	}

	public function getPlayer(): Player{return $this->player;}
	public function getSettings(): ?AimLabSettings{return $this->settings;}
	public function getConfig(): Config{return $this->config;}

	public function setInLobby(bool $in_lobby): void{$this->in_lobby = $in_lobby;}
	public function isInLobby(): bool{return $this->in_lobby;}

	function giveItems(): void{
		$this->player->getInventory()->clearAll();
		if ($this->isInLobby()) {
			$this->player->getInventory()->setItem(0, PlayItem::GET()->setUseCallback(function (Player $player): void{
				$this->ingame();
			}));
		} else {
			$this->player->getInventory()->setItem(0, LeaveItem::GET()->setUseCallback(function (Player $player): void{
			}));
		}
		$this->player->getInventory()->setItem(8, SettingsItem::GET()->setUseCallback(function (Player $player): void{
		}));
	}

	private function ingame(): void{
		$this->in_lobby = false;
		$this->giveItems();
		foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) $onlinePlayer->hidePlayer($this->player);
	}

	public function __destruct(){
		foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) $onlinePlayer->showPlayer($this->player);
	}
}
