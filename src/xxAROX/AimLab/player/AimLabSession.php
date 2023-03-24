<?php
declare(strict_types=1);
namespace xxAROX\AimLab\player;
use muqsit\simplepackethandler\interceptor\PacketInterceptor;
use muqsit\simplepackethandler\interceptor\PacketInterceptorListener;
use muqsit\simplepackethandler\SimplePacketHandler;
use pocketmine\entity\effect\Effect;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\event\EventPriority;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\Filesystem;
use pocketmine\world\World;
use pocketmine\world\WorldException;
use Ramsey\Uuid\Uuid;
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
	protected ?World $world = null;
	/** @var array<AimEntity> */
	public array $aim_entities = [];

	protected bool $in_lobby = true;

	protected int $hits = 0;
	protected int $failed_hits = 0;

	/**
	 * AimLabSession constructor.
	 * @param Player $player
	 */
	public function __construct(protected Player $player){
		$this->config = new Config(AimLabPlugin::getInstance()->getDataFolder() . "players/" . (empty($player->getXuid()) ? $player->getName() : $player->getXuid()) . ".json");
		$this->settings = new AimLabSettings($this);
		$this->giveItems();

		$this->player->setGamemode(GameMode::ADVENTURE());
		$this->player->getHungerManager()->setEnabled(false);
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
	public function getWorld(): ?World{return $this->world;}

	public function setInLobby(bool $in_lobby): void{$this->in_lobby = $in_lobby;}
	public function isInLobby(): bool{return $this->in_lobby;}
	public function hit(): void{$this->hits++;}
	public function failed_hit(): void{$this->failed_hits++;}

	function giveItems(): void{
		$this->player->getInventory()->clearAll();

		$this->player->getInventory()->setItem($this->isInLobby() ? 0 : 8, ($this->isInLobby() ? PlayItem::GET() : LeaveItem::GET())->setClickAirCallback(function (Player $player): void{$this->ingame();}));
		$this->player->getInventory()->setItem($this->isInLobby() ? 8 : 7, SettingsItem::GET()->setUseCallback(function (Player $player): void{$player->sendForm($this->settings->getForm());}));
	}

	private function ingame(): void{
		if ($this->in_lobby) {
			$this->in_lobby = false;
			$worldName = Uuid::uuid4()->toString();
			Filesystem::recursiveCopy(AimLabPlugin::getInstance()->getDataFolder() . "aim_lab_world/", Server::getInstance()->getDataPath() . "worlds/$worldName");
			if (Server::getInstance()->getWorldManager()->loadWorld($worldName)) $this->world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
			else throw new WorldException("Couldn't load new aim lab world!");
			$this->player->teleport($this->world->getSafeSpawn());
			$this->player->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), 20 * 11111, 1, false));
		} else {
			$this->player->getEffects()->remove(VanillaEffects::NIGHT_VISION());
			$this->deleteWorld();
			$this->in_lobby = true;
			$this->player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
		}
		$this->giveItems();
	}

	public function destroy(): void{
		$this->settings->save();
		if (!$this->isInLobby()) $this->deleteWorld();
	}

	private function deleteWorld(): void{
		if (count($this->aim_entities) > 0) {
			foreach ($this->aim_entities as $aimEntity) $aimEntity->flagForDespawn();
		}
		if (!is_null($this->world)) {
			$worldName = $this->world->getFolderName();
			Server::getInstance()->getWorldManager()->unloadWorld($this->world);
			Filesystem::recursiveUnlink(Server::getInstance()->getDataPath() . "worlds/$worldName");
		}
	}
}
