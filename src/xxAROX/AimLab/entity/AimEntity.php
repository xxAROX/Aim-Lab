<?php
declare(strict_types=1);
namespace xxAROX\AimLab\entity;
use JetBrains\PhpStorm\Pure;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;
use pocketmine\world\biome\model\ColorData;
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
	protected int $targetColor = 0xffffff;

	public function __construct(Location $location, ?CompoundTag $nbt = null){
		parent::__construct($location, $nbt);
	}

	protected function initEntity(CompoundTag $nbt) : void {
        parent::initEntity($nbt);
        $this->setTargetColor();
    }

	protected function syncNetworkData(EntityMetadataCollection $properties) : void{
        parent::syncNetworkData($properties);

        $r = ($this->targetColor >> 16) & 0xFF;
        $g = ($this->targetColor >> 8) & 0xFF;
        $b = $this->targetColor & 0xFF;

        $properties->setInt(EntityMetadataProperties::VARIANT, $r);
        $properties->setInt(EntityMetadataProperties::MARK_VARIANT, $g);
        $properties->setInt(EntityMetadataProperties::TRADE_TIER, $b);
    }

    protected function getInitialSizeInfo() : EntitySizeInfo {
        return new EntitySizeInfo(0.55, 0.55); 
    }

    public function setTargetColor(?int $hexVal = null) : void {
		if (is_null($hexVal)) $hexVal = mt_rand(0, 0xFFFFFF);
		$this->targetColor = $hexVal;
		$r = ($hexVal >> 16) & 0xFF;
        $g = ($hexVal >> 8) & 0xFF;
        $b = $hexVal & 0xFF;

        $this->getNetworkProperties()->setInt(EntityMetadataProperties::VARIANT, $r);
        $this->getNetworkProperties()->setInt(EntityMetadataProperties::MARK_VARIANT, $g);
        $this->getNetworkProperties()->setInt(EntityMetadataProperties::TRADE_TIER, $b);
    }

	public function onDispose(): void{
		unset($this->session->aim_entities[$this->getId()]);
		parent::onDispose();
	}

	protected function entityBaseTick(int $tickDiff = 1) : bool{
		if ($this->session === null) return parent::entityBaseTick($tickDiff);
		$speed = $this->session->getSettings()->speed;
		$baseLifetime = $this->session->getSettings()->lifetime_seconds;

		$speedFactor = 1 + ($speed / 10); 
		$dynamicLifetime = $baseLifetime / $speedFactor;

		if ($this->ticksLived / 20 > $dynamicLifetime) {
			$this->session->failed_hit();
			$this->flagForDespawn();
			return true;
        }
		return parent::entityBaseTick($tickDiff);
	}

	public function attack(EntityDamageEvent $source): void{
		$source->cancel();
		if ($source instanceof EntityDamageByEntityEvent && $source->getDamager()->getId() === $this->session->getPlayer()->getId()) {
			$this->session->hit();
			$this->flagForDespawn();
		}
		parent::attack($source);
	}

	public function spawnTo(Player $player): void{
		if (is_null($this->session)) $this->flagForDespawn();
		parent::spawnTo($player);
	}

	public function getMaxHealth(): int{return 1;}
	public function setSession(?AimLabSession $session): void{$this->session = $session;}
	public function getSession(): AimLabSession{return $this->session;}
	public static function getNetworkTypeId(): string{return self::IDENTIFIER;}
	public function canSaveWithChunk(): bool{return false;}

	public function getInitialDragMultiplier(): float{return 0;}
	public function getInitialGravity(): float{return 0;}
}
