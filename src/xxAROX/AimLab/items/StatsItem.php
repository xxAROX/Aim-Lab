<?php
declare(strict_types=1);
namespace xxAROX\AimLab\items;
use customiesdevs\customies\item\CreativeInventoryInfo;
use customiesdevs\customies\item\CustomiesItemFactory;
use customiesdevs\customies\item\ItemComponents;
use customiesdevs\customies\item\ItemComponentsTrait;
use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;

/**
 * Class StatsItem
 * @package xxAROX\AimLab\items
 * @author Jan Sohn / xxAROX
 * @date 16. July, 2026
 * @project Aim-Lab
 */
class StatsItem extends Item implements ItemComponents {
	const IDENTIFIER = "aim_lab:stats";
	use ItemComponentsTrait;
	use BaseItem;

	public function __construct(ItemIdentifier $identifier, string $name = "Unknown") {
		parent::__construct($identifier, $name);
		$this->initComponent(self::IDENTIFIER, CreativeInventoryInfo::DEFAULT());
		$this->setUseCooldown(1, "aim_lab_items");
	}

	public function getCooldownTicks(): int { return 20; }
	public function getMaxStackSize(): int { return 1; }
	
	static function GET(): self {
		return CustomiesItemFactory::getInstance()->get(self::IDENTIFIER);
	}
}
