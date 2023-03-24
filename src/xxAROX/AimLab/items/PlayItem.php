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
 * Class PlayItem
 * @package xxAROX\AimLab\items
 * @author Jan Sohn / xxAROX
 * @date 24. March, 2023 - 20:32
 * @ide PhpStorm
 * @project Aim-Lab
 */
class PlayItem extends Item implements ItemComponents{
	const IDENTIFIER = "aim_lab:play";
	use ItemComponentsTrait;
	use BaseItem;

	public function __construct(ItemIdentifier $identifier, string $name = "Unknown"){
		parent::__construct($identifier, $name);
		$this->initComponent(self::IDENTIFIER, CreativeInventoryInfo::DEFAULT());
	}

	public function getMaxStackSize(): int{return 1;}
	static function GET(): self{return CustomiesItemFactory::getInstance()->get(self::IDENTIFIER);}
}
