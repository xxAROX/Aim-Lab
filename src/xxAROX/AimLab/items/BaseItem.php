<?php
/*
 *    Copyright 2023 Jan Sohn / xxAROX
 *
 *    Licensed under the Apache License, Version 2.0 (the "License");
 *    you may not use this file except in compliance with the License.
 *    You may obtain a copy of the License at
 *
 *        http://www.apache.org/licenses/LICENSE-2.0
 *
 *    Unless required by applicable law or agreed to in writing, software
 *    distributed under the License is distributed on an "AS IS" BASIS,
 *    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *    See the License for the specific language governing permissions and
 *    limitations under the License.
 *
 */

declare(strict_types=1);
namespace xxAROX\AimLab\items;
use Closure;
use DaveRandom\CallbackValidator\BuiltInTypes;
use DaveRandom\CallbackValidator\CallbackType;
use DaveRandom\CallbackValidator\InvalidCallbackException;
use DaveRandom\CallbackValidator\ParameterType;
use DaveRandom\CallbackValidator\ReturnType;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\item\ItemUseResult;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Utils;
use TypeError;
use xxAROX\AimLab\AimLabPlugin;
use xxAROX\AimLab\util\TransactionCallbackStorage;


/**
 * Class BaseItem
 * @package xxAROX\utils\xxAROX\utils\items
 * @author Jan Sohn / xxAROX
 * @date 20. July, 2022 - 19:37
 * @ide PhpStorm
 * @project Core
 */
trait BaseItem{
	private ?int $transactionCallbackId = null;
	private ?Closure $entityInteractCallback = null; // Closure(Player $player, Entity $entity, Block $block): bool
	private ?Closure $entityAttackCallback = null; // Closure(Player $player, Entity $victim): bool
	private ?Closure $blockInteractCallback = null; // Closure(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector): bool
	private ?Closure $clickAirCallback = null; // Closure(Player $player, Vector3 $directionVector): ItemUseResult
	private ?Closure $releaseUsingCallback = null; // Closure(Player $player): ItemUseResult
	private ?Closure $heldCallback = null; // Closure(Player $player): void
	private ?Closure $useCallback = null; // Closure(Player $player): void

	/**
	 * Function getCooldownTicks
	 * @description Returns the number of ticks a player must wait before activating this item again.
	 * @return int
	 */
	public function getCooldownTicks(): int{
		return 10;
	}

	/**
	 * Function onClickAir
	 * @param Player $player
	 * @param Vector3 $directionVector
	 * @return ItemUseResult
	 */
	public function onClickAir(Player $player, Vector3 $directionVector): ItemUseResult{
		/** @var Item $this */
		if ($player->hasItemCooldown($this)) return ItemUseResult::FAIL();
		if (!is_null($this->clickAirCallback)) ($this->clickAirCallback)($player, $directionVector);
		else if (!is_null($this->useCallback)) ($this->useCallback)($player);
		$player->resetItemCooldown($this, 10);
		return ItemUseResult::FAIL();
	}

	/**
	 * Function onInteractBlock
	 * @param Player $player
	 * @param Block $blockReplace
	 * @param Block $blockClicked
	 * @param int $face
	 * @param Vector3 $clickVector
	 * @return ItemUseResult
	 */
	public function onInteractBlock(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector): ItemUseResult{
		/** @var Item $this */
		if ($player->hasItemCooldown($this)) return ItemUseResult::FAIL();
		if (!is_null($this->blockInteractCallback)) ($this->blockInteractCallback)($player, $blockReplace, $blockClicked, $face, $clickVector);
		else if (!is_null($this->useCallback)) ($this->useCallback)($player);
		$player->resetItemCooldown($this, 10);
		return ItemUseResult::FAIL();
	}

	/**
	 * Function onReleaseUsing
	 * @param Player $player
	 * @return ItemUseResult
	 */
	public function onReleaseUsing(Player $player): ItemUseResult{
		/** @var Item $this */
		if ($player->hasItemCooldown($this)) return ItemUseResult::FAIL();
		if (!is_null($this->releaseUsingCallback)) ($this->releaseUsingCallback)($player);
		else if (!is_null($this->useCallback)) ($this->useCallback)($player);
		$player->resetItemCooldown($this);
		return ItemUseResult::FAIL();
	}

	/**
	 * Function onAttackE
	 * @param Player $player
	 * @param Entity $victim
	 * @return bool
	 */
	public function onAttackE(Player $player, Entity $victim): bool{
		/** @var Item $this */
		if ($player->hasItemCooldown($this)) return false;
		if (!is_null($this->entityAttackCallback)) ($this->entityAttackCallback)($player, $victim);
		else if (!is_null($this->useCallback)) ($this->useCallback)($player);
		$player->resetItemCooldown($this);
		return false;
	}

	/**
	 * Function onInteractEntity
	 * @param Player $player
	 * @param Entity $entity
	 * @param Vector3 $clickVector
	 * @return bool
	 */
	public function onInteractEntity(Player $player, Entity $entity, Vector3 $clickVector): bool{
		/** @var Item $this */
		if ($player->hasItemCooldown($this)) return false;
		if (!is_null($this->entityInteractCallback)) ($this->entityInteractCallback)($player, $entity);
		else if (!is_null($this->useCallback)) ($this->useCallback)($player);
		$player->resetItemCooldown($this);
		return false;
	}

	/**
	 * Function onHeld
	 * @param Player $player
	 * @return bool for cancel the ItemHeldEvent event
	 */
	public function onHeld(Player $player): bool{
		/** @var Item $this */
		if ($player->hasItemCooldown($this)) return true;
		if (!is_null($this->heldCallback)) ($this->heldCallback)($player);
		$player->resetItemCooldown($this);
		return false;
	}

	/**
	 * Function init
	 * @param Player $holder
	 * @param string $custom_name
	 * @return void
	 */
	protected function init(Player $holder, string $custom_name = ""){
		/** @var Item $this */
		$player = $holder;
		if (!empty($custom_name)) $this->setCustomName("§r${custom_name}§r");
	}

	/**
	 * Function testCallback
	 * @param CallbackType $callbackType
	 * @param null|callable|Closure $callback
	 * @return void
	 */
	private function testCallback(CallbackType $callbackType, null|callable|Closure $callback): void{
		if (is_null($callback)) return;
		if (is_callable($callback)) $callback = Closure::fromCallable($callback);
		try {
			Utils::validateCallableSignature($callbackType, $callback);
		} catch (TypeError | InvalidCallbackException $e) {
			throw new InvalidCallbackException($e->getMessage());
		}
	}

	/**
	 * Function setUseCallback
	 * @param null|Closure $callback (Player $player): void
	 * @return static
	 */
	public function setUseCallback(?Closure $callback = null): static{
		$this->testCallback(new CallbackType(
			new ReturnType(),
			new ParameterType("player", Player::class)
		), $callback);
		$this->useCallback = $callback;
		return $this;
	}

	/**
	 * Function setHeldCallback
	 * @param null|Closure $callback (Player $player): bool
	 * @return $this
	 */
	public function setHeldCallback(?Closure $callback = null): static{
		$this->testCallback(new CallbackType(
			new ReturnType(BuiltInTypes::BOOL),
			new ParameterType("player", Player::class)
		), $callback);
		$this->heldCallback = $callback;
		return $this;
	}

	/**
	 * Function setEntityInteractCallback
	 * @param null|Closure $callback (Player $player, Entity $entity)): void
	 * @return $this
	 */
	public function setEntityInteractCallback(?Closure $callback = null): static{
		$this->testCallback(new CallbackType(
			new ReturnType(),
			new ParameterType("player", Player::class),
			new ParameterType("entity", Entity::class),
		), $callback);
		$this->entityInteractCallback = $callback;
		return $this;
	}

	/**
	 * Function setEntityAttackCallback
	 * @param null|Closure $callback (Player $player, Entity $entity)): void
	 * @return $this
	 */
	public function setEntityAttackCallback(?Closure $callback = null): static{
		$this->testCallback(new CallbackType(
			new ReturnType(),
			new ParameterType("player", Player::class),
			new ParameterType("entity", Entity::class),
		), $callback);
		$this->entityAttackCallback = $callback;
		return $this;
	}

	/**
	 * Function setBlockInteractCallback
	 * @param null|Closure $callback (Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector)): void
	 * @return $this
	 */
	public function setBlockInteractCallback(?Closure $callback = null): static{
		$this->testCallback(new CallbackType(
			new ReturnType(),
			new ParameterType("player", Player::class),
			new ParameterType("blockReplace", Block::class),
			new ParameterType("blockClicked", Block::class),
			new ParameterType("face", BuiltInTypes::INT),
			new ParameterType("clickVector", Vector3::class),
		), $callback);
		$this->blockInteractCallback = $callback;
		return $this;
	}

	/**
	 * Function setClickAirCallback
	 * @param null|Closure $callback (Player $player, Vector3 $clickVector)): void
	 * @return static
	 */
	public function setClickAirCallback(?Closure $callback = null): static{
		$this->testCallback(new CallbackType(
			new ReturnType(),
			new ParameterType("player", Player::class),
			new ParameterType("directionVector", Vector3::class),
		), $callback);
		$this->clickAirCallback = $callback;
		return $this;
	}

	/**
	 * Function setReleaseUsingCallback
	 * @param null|Closure $callback (Player $player)): void
	 * @return $this
	 */
	public function setReleaseUsingCallback(?Closure $callback = null): static{
		$this->testCallback(new CallbackType(
			new ReturnType(),
			new ParameterType("player", Player::class),
		), $callback);
		$this->releaseUsingCallback = $callback;
		return $this;
	}

	/**
	 * Function setTransactionCallback
	 * @param null|Closure $callback (Player $player, Item $item, Item $replaced): void
	 * @param null|bool $closeInventory
	 * @return $this
	 */
	public function setTransactionCallback(?Closure $callback = null, bool $closeInventory = false): static{
		/** @var Item $this */
		$this->testCallback(new CallbackType(
			new ReturnType(),
			new ParameterType("player", Player::class),
			new ParameterType("item", Item::class),
			new ParameterType("replaced", Item::class),
		), $callback);
		if (is_null($callback)) {
			$nbt = $this->getNamedTag();
			$nbt->removeTag("__transactionCallbackIdentifier");
			$this->setNamedTag($nbt);
			TransactionCallbackStorage::unregisterTransactionCallback($this->transactionCallbackId);
			$this->transactionCallbackId = null;
		} else {
			if ($closeInventory) {
				$callback = function (Player $player, Item $item, Item $replaced) use ($callback) {
					$player->removeCurrentWindow(); // NOTE: DOSN'T WORK
					AimLabPlugin::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($callback, $player, $item, $replaced): void{
						$callback($player, $item, $replaced);
					}), 5);
				};
			}
			$this->transactionCallbackId = TransactionCallbackStorage::registerTransactionCallback($callback);
			$this->setNamedTag($this->getNamedTag()->setInt("__transactionCallbackIdentifier", $this->transactionCallbackId));
		}
		return $this;
	}
}