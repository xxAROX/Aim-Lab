<?php
declare(strict_types=1);
namespace xxAROX\AimLab\command;
use CortexPE\Commando\BaseCommand;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;


/**
 * Class AimLabCommand
 * @package xxAROX\AimLab\command
 * @author Jan Sohn / xxAROX
 * @date 24. March, 2023 - 19:05
 * @ide PhpStorm
 * @project Aim-Lab
 */
class AimLabCommand extends BaseCommand{
	public function __construct(Plugin $plugin, string $name, string $description = "", array $aliases = []){
		parent::__construct($plugin, $name, $description, $aliases);
	}

	protected function prepare(): void{
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void{
	}
}
