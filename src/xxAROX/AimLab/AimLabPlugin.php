<?php
declare(strict_types=1);
namespace xxAROX\AimLab;
use customiesdevs\customies\entity\CustomiesEntityFactory;
use customiesdevs\customies\item\CustomiesItemFactory;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\SingletonTrait;
use ReflectionClass;
use WeakMap;
use xxAROX\AimLab\entity\AimEntity;
use xxAROX\AimLab\items\LeaveItem;
use xxAROX\AimLab\items\PlayItem;
use xxAROX\AimLab\items\SettingsItem;
use xxAROX\AimLab\util\RPGen;


/**
 * Class AimLabPlugin
 * @package xxAROX\AimLab
 * @author Jan Sohn / xxAROX
 * @date 24. March, 2023 - 19:04
 * @ide PhpStorm
 * @project Aim-Lab
 */
final class AimLabPlugin extends PluginBase{
	use SingletonTrait{
		setInstance as private;
		reset as private;
	}

	private const RP_VERSION = 5;

	protected RPGen $RPGen;
	public WeakMap $sessions;

	protected function onLoad(): void{
		$this->sessions = new WeakMap();
		self::setInstance($this);
		foreach ($this->getResources() as $resource => $_) $this->saveResource($resource, true);
	}

	protected function onEnable(): void{
		$this->RPGen = new RPGen(
			$this->getDataFolder() . "{$this->getName()}.zip",
			$this->getDescription()->getName(),
			$this->getDescription()->getDescription(),
			[0,0,self::RP_VERSION]
		);
		foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->getDataFolder() . "RP/", \FilesystemIterator::SKIP_DOTS)) as $resource => $splFile){
			if ($splFile->isFile()) {
				var_dump([
					"to" => substr((string) $resource, strlen($this->getDataFolder() . "RP/")),
					"from" => $resource,
				]);
				$this->RPGen->addFromString(substr((string) $resource, strlen($this->getDataFolder() . "RP/")), file_get_contents($resource));
			}
		}
		$pack = $this->RPGen->generate();

		$manager = $this->getServer()->getResourcePackManager();
		$reflection = new ReflectionClass($manager);

		$packsProperty = $reflection->getProperty("resourcePacks");
		$packsProperty->setAccessible(true);
		$currentResourcePacks = $packsProperty->getValue($manager);

		$uuidProperty = $reflection->getProperty("uuidList");
		$uuidProperty->setAccessible(true);
		$currentUUIDPacks = $uuidProperty->getValue($manager);

		$property = $reflection->getProperty("serverForceResources");
		$property->setAccessible(true);
		$property->setValue($manager, true);

		$currentUUIDPacks[strtolower($pack->getPackId())] = $currentResourcePacks[] = $pack;

		$packsProperty->setValue($manager, $currentResourcePacks);
		$uuidProperty->setValue($manager, $currentUUIDPacks);

		@mkdir($this->getDataFolder() . "players/");

		CustomiesEntityFactory::getInstance()->registerEntity(AimEntity::class, AimEntity::IDENTIFIER);

		CustomiesItemFactory::getInstance()->registerItem(PlayItem::class, PlayItem::IDENTIFIER, "Play aim lab");
		CustomiesItemFactory::getInstance()->registerItem(LeaveItem::class, LeaveItem::IDENTIFIER, "leave aim lab");
		CustomiesItemFactory::getInstance()->registerItem(SettingsItem::class, SettingsItem::IDENTIFIER, "Settings aim lab");

		$this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void{
			foreach ($this->sessions->getIterator() as $player => $session) $session->tick();
		}), 1);#
		$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
	}

	protected function onDisable(): void{
	}
}
