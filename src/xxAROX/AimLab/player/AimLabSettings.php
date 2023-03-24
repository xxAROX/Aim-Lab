<?php
declare(strict_types=1);
namespace xxAROX\AimLab\player;
use JetBrains\PhpStorm\Pure;
use pocketmine\player\Player;
use xxAROX\forms\elements\Label;
use xxAROX\forms\elements\Slider;
use xxAROX\forms\elements\StepSlider;
use xxAROX\forms\types\CustomForm;


/**
 * Class AimLabSettings
 * @package xxAROX\AimLab\player
 * @author Jan Sohn / xxAROX
 * @date 24. March, 2023 - 20:42
 * @ide PhpStorm
 * @project Aim-Lab
 */
final class AimLabSettings{
	public float $speed = 1;
	public float $scale_min = 1;
	public float $scale_max = 1;

	#[Pure]
	public function __construct(protected AimLabSession $session){
		$config = $session->getConfig()->getAll();
		$this->speed = floatval($config["speed"] ?? 1);
	}

	public function save(): void{
		$this->session->getConfig()->set("speed", $this->speed);

		if ($this->session->getConfig()->hasChanged()) $this->session->getConfig()->save();
	}

	public function getForm(): CustomForm{
		$elements = [
			new Slider("Speed", 0.1, 3.5, 0.1, 1, function (Player $player, Slider $e):void{$this->speed = $e->getValue();}),
			new Slider("Min. scale", 0.3, 1, 0.1, 1, function (Player $player, Slider $e):void{$this->scale_min = $e->getValue();}),
			new Slider("Max. scale", 0.3, 1.5, 0.1, 1, function (Player $player, Slider $e):void{$this->scale_max = $e->getValue();}),
		];

		return new CustomForm(
			"§3Aim-Lab settings",
			$elements
		);
	}
}
