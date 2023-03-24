<?php
declare(strict_types=1);
namespace xxAROX\AimLab\player;
use JetBrains\PhpStorm\Pure;


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

	#[Pure]
	public function __construct(protected AimLabSession $session){
		$config = $session->getConfig()->getAll();
		$this->speed = floatval($config["speed"] ?? 1);
	}

	public function save(): void{
		$this->session->getConfig()->set("speed", $this->speed);

		if ($this->session->getConfig()->hasChanged()) $this->session->getConfig()->save();
	}
}
