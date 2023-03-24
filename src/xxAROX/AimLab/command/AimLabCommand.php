<?php
declare(strict_types=1);
namespace xxAROX\AimLab\command;
use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use xxAROX\AimLab\AimLabPlugin;
use xxAROX\AimLab\entity\AimEntity;


/**
 * Class AimLabCommand
 * @package xxAROX\AimLab\command
 * @author Jan Sohn / xxAROX
 * @date 24. March, 2023 - 19:05
 * @ide PhpStorm
 * @project Aim-Lab
 */
class AimLabCommand extends BaseCommand{
	public function __construct(AimLabPlugin $plugin){
		parent::__construct($plugin, "aimlab", "Aim-Lab command", ["alab"]);
	}

	protected function prepare(): void{
		$this->addConstraint(new InGameRequiredConstraint($this));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void{
		if (!$sender instanceof Player) return;
		$e = new AimEntity($sender->getLocation());
		$e->spawnToAll();
	}
}
