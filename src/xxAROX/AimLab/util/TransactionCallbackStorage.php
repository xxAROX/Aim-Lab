<?php
declare(strict_types=1);
namespace xxAROX\AimLab\util;
use Closure;


/**
 * Class TransactionCallbackStorage
 * @package xxAROX\AimLab\util
 * @author Jan Sohn / xxAROX
 * @date 24. March, 2023 - 20:38
 * @ide PhpStorm
 * @project Aim-Lab
 */
class TransactionCallbackStorage{
	/** @var Closure[](Closure(Player $player, Item $item, Item $replaced): void)) $transaction */
	private static array $transactionCallbacks = [];
	private static int $nextTransactionCallbackId = 0;

	/**
	 * Function registerTransactionCallback
	 * @param Closure(Player $player, Item $item, Item $replaced): void $callback
	 * @return int
	 */
	static final function registerTransactionCallback(Closure $callback): int{
		$id = ++self::$nextTransactionCallbackId;
		self::$transactionCallbacks[$id] = $callback;
		return $id;
	}

	/**
	 * Function unregisterTransactionCallback
	 * @param int $id
	 * @return void
	 */
	static final function unregisterTransactionCallback(int $id): void{
		unset(self::$transactionCallbacks[$id]);
	}

	/**
	 * Function getTransactionCallbacks
	 * @return array
	 */
	public static function getTransactionCallbacks(): array{
		return self::$transactionCallbacks;
	}
}
