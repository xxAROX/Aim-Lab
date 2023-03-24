<?php
declare(strict_types=1);
namespace xxAROX\AimLab\entity;
use JetBrains\PhpStorm\Pure;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use xxAROX\AimLab\player\AimLabSession;


/**
 * Class AimEntity
 * @package xxAROX\AimLab\entity
 * @author Jan Sohn / xxAROX
 * @date 24. March, 2023 - 19:10
 * @ide PhpStorm
 * @project Aim-Lab
 */
class AimEntity extends Entity{
	const IDENTIFIER = "aim_lab:entity";
	protected ?AimLabSession $session = null;

	public function __construct(Location $location, ?CompoundTag $nbt = null){parent::__construct($location, $nbt);}

	public function onDispose(): void{
		unset($this->session->aim_entities[$this->getId()]);
		parent::onDispose();
	}

	public function spawnTo(Player $player): void{
		if (is_null($this->session)) $this->flagForDespawn();
		parent::spawnTo($player);
	}

	public function getMaxHealth(): int{return 1;}
	public function setSession(?AimLabSession $session): void{$this->session = $session;}
	public function getSession(): AimLabSession{return $this->session;}
	#[Pure] protected function getInitialSizeInfo(): EntitySizeInfo{return new EntitySizeInfo(0.55, 0.55);}
	public static function getNetworkTypeId(): string{return self::IDENTIFIER;}
}
