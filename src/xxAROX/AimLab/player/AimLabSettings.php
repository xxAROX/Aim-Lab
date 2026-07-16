<?php
declare(strict_types=1);
namespace xxAROX\AimLab\player;
use JetBrains\PhpStorm\Pure;
use pocketmine\player\Player;
use xxAROX\forms\elements\Label;
use xxAROX\forms\elements\Slider;
use xxAROX\forms\elements\StepSlider;
use xxAROX\forms\types\CustomForm;
use xxAROX\forms\types\CustomFormResponse;


/**
 * Class AimLabSettings
 * @package xxAROX\AimLab\player
 * @author Jan Sohn / xxAROX
 * @date 24. March, 2023 - 20:42
 * @ide PhpStorm
 * @project Aim-Lab
 */
final class AimLabSettings{
	const SCALE_MINIMAL_VALUE = 0.3;
	const SCALE_MAXIMAL_VALUE = 2;
	public float $speed = 1;
	public float $scale_min = 1;
	public float $scale_max = 1.5;
	public int $lifetime_seconds = 5;
	public int $max_misses = 10; // Neu hinzugefügt

	#[Pure]
	public function __construct(protected AimLabSession $session){
		$config = $session->getConfig()->getAll();
		$this->speed = floatval($config["speed"] ?? 1);
		$this->scale_min = floatval($config["scale_min"] ?? 1);
		$this->scale_max = floatval($config["scale_max"] ?? 1.5);
		$this->lifetime_seconds = intval($config["lifetime_seconds"] ?? 5);
		$this->max_misses = intval($config["max_misses"] ?? 10); // Neu hinzugefügt
	}

	public function save(): void{
		$this->session->getConfig()->set("speed", number_format($this->speed, 2,".",""));
		$min = min($this->scale_min, $this->scale_max);
		$max = max($this->scale_min, $this->scale_max);
		$this->session->getConfig()->set("scale_min", number_format($min, 2,".",""));
		$this->session->getConfig()->set("scale_max", number_format($max,2,".",""));
		$this->session->getConfig()->set("lifetime_seconds", $this->lifetime_seconds);
		$this->session->getConfig()->set("max_misses", $this->max_misses); // Neu hinzugefügt

		if ($this->session->getConfig()->hasChanged()) $this->session->getConfig()->save();
	}

	public function getForm(): CustomForm{
		$elements = [
			new Slider("Speed",      0.1, 3.5,                     0.25, $this->speed, function (Player $player, Slider $e):void{$this->speed = $e->getValue();}),
			new Slider("Min. scale", self::SCALE_MINIMAL_VALUE, self::SCALE_MAXIMAL_VALUE, 0.1,  $this->scale_min, function (Player $player, Slider $e):void{$this->scale_min = $e->getValue();}),
			new Slider("Max. scale", self::SCALE_MINIMAL_VALUE, self::SCALE_MAXIMAL_VALUE, 0.1,  $this->scale_max, function (Player $player, Slider $e):void{$this->scale_max = $e->getValue();}),
			new Slider("Lifetime", 1, 10, 1, $this->lifetime_seconds, function (Player $player, Slider $e):void{$this->lifetime_seconds = intval($e->getValue());}),
			new Slider("Max. Misses", 1, 50, 1, $this->max_misses, function (Player $player, Slider $e):void{$this->max_misses = (int)$e->getValue();}), // Neu hinzugefügt
		];

		return new CustomForm(
			"§3Aim-Lab settings",
			$elements,
			null,
			function (Player $player, CustomFormResponse $response):void{$this->save();}
		);
	}
}
