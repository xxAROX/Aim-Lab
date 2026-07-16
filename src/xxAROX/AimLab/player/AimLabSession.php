<?php
declare(strict_types=1);
namespace xxAROX\AimLab\player;

use Closure;
use muqsit\simplepackethandler\interceptor\PacketInterceptor;
use muqsit\simplepackethandler\interceptor\PacketInterceptorListener;
use muqsit\simplepackethandler\SimplePacketHandler;
use pocketmine\entity\effect\Effect;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\event\EventPriority;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\Filesystem;
use pocketmine\world\particle\FlameParticle;
use pocketmine\world\World;
use pocketmine\world\WorldException;
use Ramsey\Uuid\Uuid;
use Random\IntervalBoundary;
use Random\Randomizer;
use xxAROX\AimLab\AimLabPlugin;
use xxAROX\AimLab\entity\AimEntity;
use xxAROX\AimLab\items\LeaveItem;
use xxAROX\AimLab\items\PlayItem;
use xxAROX\AimLab\items\SettingsItem;
use xxAROX\forms\types\CustomForm;
use xxAROX\forms\elements\Label;

/**
 * Class AimLabSession
 * @package xxAROX\AimLab\player
 * @author Jan Sohn / xxAROX
 * @date 24. March, 2023 - 20:42
 * @ide PhpStorm
 * @project Aim-Lab
 */
final class AimLabSession{
    protected Config $config;
    protected AimLabSettings $settings;
    protected ?World $world = null;
    /** @var array<AimEntity> */
    public array $aim_entities = [];

    protected bool $in_lobby = true;

    protected int $hits = 0;
    protected int $failed_hits = 0;

    /**
     * AimLabSession constructor.
     * @param Player $player
     */
    public function __construct(protected Player $player){
        $this->config = new Config(AimLabPlugin::getInstance()->getDataFolder() . "players/" . (empty($player->getXuid()) ? $player->getName() : $player->getXuid()) . ".json");
        $this->settings = new AimLabSettings($this);
        $this->giveItems();

        $this->player->setGamemode(GameMode::ADVENTURE());
        $this->player->getHungerManager()->setEnabled(false);
    }

    private function randomScale(): float{
        return $this->settings->scale_min + mt_rand() / mt_getrandmax() * ($this->settings->scale_max - $this->settings->scale_min);
    }

    public function tick(): void{
        if ($this->isInLobby()) {
            return;
        }
        $speed = (int) round($this->settings->speed);
        $tickInterval = max(1, 20 - $speed);
        if (Server::getInstance()->getTick() %$tickInterval === 0) {
            $v = $this->newRandomVec();
            $this->player->getWorld()->addParticle($v, new FlameParticle());
            $aim = new AimEntity(new Location($v->x, $v->y, $v->z, $this->player->getWorld(), 0, 0));
            $aim->setScale($this->randomScale());
            $aim->setSession($this);
            $this->aim_entities[$aim->getId()] = $aim;
            $aim->spawnTo($this->player);
            $aim->spawnToAll();
        }
    }

    /**
     * Calculates a target spawn position directly in front of the player's eyes,
     * offset horizontally and vertically relative to their looking direction.
     * Safely prevents spawning targets inside the ground.
     */
    private function newRandomVec(): Vector3{
        $player = $this->player;
        $eyePos = $player->getEyePos(); // Starting point at player's eye height
        $direction = $player->getDirectionVector(); // Normalized vector of where they look

        // Distance: How many blocks in front of the player the targets should spawn
        $distance = 1.5; 

        // This is the center point directly in front of the player's face
        $center = $eyePos->addVector($direction->multiply($distance));

        // We calculate the "right" vector perpendicular to the player's horizontal view (Yaw)
        $yawRad = deg2rad($player->getLocation()->getYaw());
        $right = new Vector3(-cos($yawRad), 0, -sin($yawRad));

        // Random offsets (spread):
        // - Left/Right: up to 2.2 blocks horizontally
        // - Up/Down: up to 0.6 blocks vertically
        $horizontalOffset = mt_rand(-220, 220) / 100;
        $verticalOffset = mt_rand(-60, 60) / 100;

        // Combine the vectors: Center + (Right * horizontalOffset) + (Up * verticalOffset)
        $spawnPos = $center
            ->addVector($right->multiply($horizontalOffset))
            ->addVector(new Vector3(0, $verticalOffset, 0));

        // COLLISION PROTECTION: Prevents the ball from clipping into the ground/floor.
        // Assuming player stands on floor, floor Y is player Y - 1. 
        // We set minimum target Y to player foot Y + 0.3 to keep targets safely visible.
        $minY = $player->getPosition()->getY() + 0.3;
        if ($spawnPos->y < $minY) {
            $spawnPos->y = $minY;
        }

        return $spawnPos;
    }

    public function getPlayer(): Player{return $this->player;}
    public function getSettings(): ?AimLabSettings{return $this->settings;}
    public function getConfig(): Config{return $this->config;}
    public function getWorld(): ?World{return $this->world;}

    public function setInLobby(bool $in_lobby): void{$this->in_lobby = $in_lobby;}
    public function isInLobby(): bool{return $this->in_lobby;}
    public function hit(): void{$this->hits++;}
    
    public function failed_hit(): void{
        $this->failed_hits++;
        
        if (!$this->isInLobby() && $this->failed_hits >= $this->settings->max_misses) {
            $this->player->sendMessage("§cYou have reached the limit of missed targets ({$this->settings->max_misses})!");
            $this->ingame(); // Teleports back & triggers round end
        }
    }

    function giveItems(): void{
        $this->player->getInventory()->clearAll();

        $this->player->getInventory()->setItem(0, ($this->isInLobby() ? PlayItem::GET() : LeaveItem::GET())->setClickAirCallback(function (Player $player): void{$this->ingame();}));
        
        if ($this->isInLobby()) {
            $this->player->getInventory()->setItem(4, \xxAROX\AimLab\items\StatsItem::GET()->setClickAirCallback(function (Player $player): void{
                $this->sendAllTimeStatsForm();
            }));
        }

        $this->player->getInventory()->setItem(8, SettingsItem::GET()->setUseCallback(function (Player $player): void{$player->sendForm($this->settings->getForm());}));
    }

    private function ingame(): void{
        if ($this->in_lobby) {
            $this->hits = 0;
            $this->failed_hits = 0;
            $this->in_lobby = false;
            $worldName = "aimlab_world_".Uuid::uuid4()->toString();
            Filesystem::recursiveCopy(AimLabPlugin::getInstance()->getDataFolder() . "aim_lab_world/", Server::getInstance()->getDataPath() . "worlds/$worldName");
            if (Server::getInstance()->getWorldManager()->loadWorld($worldName)) $this->world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
            else throw new WorldException("Couldn't load new aim lab world!");
            
            // CHANGED: Pitch changed from 90 (looking down/up) to 0 (looking perfectly straight ahead)
            $goofyassHardcodedSpawnLocation = new Location(249.5, 4, 270.5, $this->world, 180, 0);
            $this->player->teleport($goofyassHardcodedSpawnLocation);
            $this->player->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), 20 * 11111, 1, false));
        } else {
            $this->player->getEffects()->remove(VanillaEffects::NIGHT_VISION());
            $this->deleteWorld();
            $this->in_lobby = true;
            $this->player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
            
            $this->saveStats();
            $this->sendStatsForm();
        }
        $this->giveItems();
    }

    /**
     * Saves every played round to a history array and updates personal best records.
     */
    private function saveStats(): void{
        $totalAttempts = $this->hits + $this->failed_hits;
        $accuracy = $totalAttempts > 0 ? round(($this->hits / $totalAttempts) * 100, 2) : 0.0;

        // Load round history list
        $rounds = $this->config->get("rounds", []);
        $rounds[] = [
            "hits" => $this->hits,
            "failed" => $this->failed_hits,
            "accuracy" => $accuracy,
            "timestamp" => time()
        ];
        $this->config->set("rounds", $rounds);

        // Highscore updating
        $bestHits = intval($this->config->get("best_hits", 0));
        $bestAccuracy = floatval($this->config->get("best_accuracy", 0.0));

        if ($this->hits > $bestHits) {
            $this->config->set("best_hits", $this->hits);
        }
        if ($accuracy > $bestAccuracy && $this->hits > 5) {
            $this->config->set("best_accuracy", $accuracy);
        }

        $this->config->save();
    }

    /**
     * Calculates average values across all recorded rounds.
     * @return array{hits: float, failed: float, accuracy: float, count: int}
     */
    private function getAverages(): array{
        $rounds = $this->config->get("rounds", []);
        $totalRounds = count($rounds);
        if ($totalRounds === 0) {
            return ["hits" => 0.0, "failed" => 0.0, "accuracy" => 0.0, "count" => 0];
        }

        $sumHits = 0;
        $sumFailed = 0;
        $sumAccuracy = 0.0;

        foreach ($rounds as $round) {
            $sumHits += $round["hits"];
            $sumFailed += $round["failed"];
            $sumAccuracy += $round["accuracy"];
        }

        return [
            "hits" => round($sumHits / $totalRounds, 1),
            "failed" => round($sumFailed / $totalRounds, 1),
            "accuracy" => round($sumAccuracy / $totalRounds, 1),
            "count" => $totalRounds
        ];
    }

    /**
     * Sends the post-game summary UI to the player.
     */
    public function sendStatsForm(): void{
        $total = $this->hits + $this->failed_hits;
        $accuracy = $total > 0 ? round(($this->hits / $total) * 100, 2) : 0;

        $elements = [
            new Label("§aGood round! Here are your training results:"),
            new Label("§fHits: §e" . $this->hits),
            new Label("§fMissed: §c" . $this->failed_hits),
            new Label("§fAccuracy: §b" . $accuracy . "%"),
            new Label("§7-------------------------------"),
            new Label("§6Personal Bests:"),
            new Label("§fBest Hits: §e" . $this->config->get("best_hits", 0)),
            new Label("§fBest Accuracy: §b" . $this->config->get("best_accuracy", 0) . "%")
        ];

        $form = new CustomForm("§dRound Statistics", $elements);
        $this->player->sendForm($form);
    }

    /**
     * Opens the total career profile including calculated averages in the Lobby.
     */
    public function sendAllTimeStatsForm(): void{
        $bestHits = $this->config->get("best_hits", 0);
        $bestAccuracy = $this->config->get("best_accuracy", 0.0);
        $averages = $this->getAverages();

        $rounds = $this->config->get("rounds", []);
        $lastRound = !empty($rounds) ? end($rounds) : null;

        $elements = [
            new Label("§aYour Aim-Lab Career Overview:"),
            new Label("§7-------------------------------"),
            new Label("§d   Career Averages (" . $averages["count"] . " Rounds Played):"),
            new Label("§fAvg. Hits: §e" . $averages["hits"]),
            new Label("§fAvg. Missed: §c" . $averages["failed"]),
            new Label("§fAvg. Accuracy: §b" . $averages["accuracy"] . "%"),
            new Label("§7-------------------------------"),
            new Label("§6   Personal Records:"),
            new Label("§fBest Hits: §e" . $bestHits),
            new Label("§fBest Accuracy: §b" . $bestAccuracy . "%")
        ];

        if ($lastRound !== null) {
            $elements[] = new Label("§7-------------------------------");
            $elements[] = new Label("§9   Last Round Stats:");
            $elements[] = new Label("§fHits: §e" . $lastRound["hits"]);
            $elements[] = new Label("§fMissed: §c" . $lastRound["failed"]);
            $elements[] = new Label("§fAccuracy: §b" . $lastRound["accuracy"] . "%");
        }

        $form = new CustomForm("§dAim-Lab Profile", $elements);
        $this->player->sendForm($form);
    }

    public function destroy(): void{
        $this->settings->save();
        if (!$this->isInLobby()) $this->deleteWorld();
    }

    private function deleteWorld(): void{
        if (count($this->aim_entities) > 0) {
            foreach ($this->aim_entities as $aimEntity) $aimEntity->flagForDespawn();
        }
        if (!is_null($this->world)) {
            $worldName = $this->world->getFolderName();
            $func = Closure::bind(function() use ($worldName): void{
                Server::getInstance()->getWorldManager()->unloadWorld($this->world);
                Filesystem::recursiveUnlink(Server::getInstance()->getDataPath() . "worlds/$worldName");
            }, $this, $this);

            if ($this->world->isDoingTick()) {
                AimLabPlugin::getInstance()->getScheduler()->scheduleTask(new ClosureTask($func));
            } else $func();
        }
    }
}
