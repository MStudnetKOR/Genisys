<?php

/*
 *
 *  _____   _____   __   _   _   _____  __    __  _____
 * /  ___| | ____| |  \ | | | | /  ___/ \ \  / / /  ___/
 * | |     | |__   |   \| | | | | |___   \ \/ /  | |___
 * | |  _  |  __|  | |\   | | | \___  \   \  /   \___  \
 * | |_| | | |___  | | \  | | |  ___| |   / /     ___| |
 * \_____/ |_____| |_|  \_| |_| /_____/  /_/     /_____/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author iTX Technologies
 * @link https://itxtech.org
 *
 */

namespace pocketmine\inventory;

use pocketmine\Player;
use pocketmine\item\Item;

class SimpleTransactionQueue implements TransactionQueue{
	
	/** @var Player[] */
	protected $player = null;
	
	/** @var \SplQueue */
	protected $transactionQueue;
	
	/** @var bool */
	protected $isExecuting = false;
	
	/** @var float */
	protected $lastExecution = -1;
	
	/** @var Inventory[] */	
	protected $inventories = [];
	
	/**
	 * @param Player $player
	 */
	public function __construct(Player $player = null){
		$this->player = $player;
		$this->transactionQueue = new \SplQueue();
	}

	/**
	 * @return Player
	 */
	public function getPlayer(){
		return $this->player;
	}
	
	/**
	 * @return \SplQueue
	 */
	public function getTransactions(){
		return $this->transactionQueue;
	}
	
	/**
	 * @return Inventory[]
	 */
	public function getInventories(){
		return $this->inventories;
	}
	
	/**
	 * @return bool
	 */
	public function isExecuting(){
		return $this->isExecuting;
	}
	
	/**
	 * @param Transaction $transaction
	 * @return bool
	 *
	 * Adds a transaction to the queue
	 * Returns true if the addition was successful, false if not.
	 */
	public function addTransaction(Transaction $transaction){
		$change = $transaction->getChange();
		
		if(@$change["in"] instanceof Item or @$change["out"] instanceof Item){
			$this->transactionQueue->enqueue($transaction);
			$this->inventories[] = $transaction->getInventory();
			return true;
		}else{
			return false;
		}
	}
	
	
	/** 
	 * @param Transaction 	$transaction
	 * @param Transaction[] &$failed
	 *
	 * Handles a failed transaction
	 */
	private function handleFailure(Transaction $transaction, array &$failed){
		$transaction->addFailure();
		if($transaction->getFailures() > 2){
			$failed[] = $transaction;
		}else{
			//Add the transaction to the back of the queue to be retried
			$this->transactionQueue->enqueue($transaction);
		}
	}
	
	/**
	 * @return Transaction[] $failed | bool
	 *
	 * Handles transaction execution
	 * Returns an array of transactions which failed
	 */
	public function execute(){
		if($this->isExecuting()){
			echo "execution already in progress\n";
			return false;
		}elseif(microtime(true) - $this->lastExecution < 0.2){
			echo "last execution time less than 4 ticks ago\n";
			return false;
		}
		echo "Starting queue execution\n";
		
		$failed = [];
		
		$this->isExecuting = true;
		while(!$this->transactionQueue->isEmpty()){
			$transaction = $this->transactionQueue->dequeue();
			$change = $transaction->getChange();
			if($change["out"] instanceof Item){
				if($transaction->getInventory()->slotContains($transaction->getSlot(), $change["out"]) or $this->player->isCreative()){
					//Allow adding nonexistent items to the crafting inventory in creative.
					echo "out transaction executing\n";

					$this->player->getCraftingInventory()->addItem($change["out"]);
					$transaction->getInventory()->setItem($transaction->getSlot(), $transaction->getTargetItem());
				}else{
					//Transaction unsuccessful
					echo "out transaction failed\n";
					
					//Relocate the transaction to the end of the list
					/*$transaction->addFailure();
					if($transaction->getFailures() > 2){
						$failed[] = $transaction;
					}else{
						//Add the transaction to the back of the queue to be retried
						$this->transactionQueue->enqueue($transaction);
					}*/
					$this->handleFailure($transaction, $failed);
					continue;
				}
			}
			if($change["in"] instanceof Item){
				if($this->player->getCraftingInventory()->contains($change["in"])){
					echo "in transaction executing\n";
					
					$this->player->getCraftingInventory()->removeItem($change["in"]);
					$transaction->getInventory()->setItem($transaction->getSlot(), $transaction->getTargetItem());
				}else{
					//Transaction unsuccessful
					echo "in transaction failed\n";
					
					//Relocate the transaction to the end of the list
					/*$transaction->addFailure();
					if($transaction->getFailures() > 2){
						$failed[] = $transaction;
					}else{
						//Add the transaction to the back of the queue to be retried
						$this->transactionQueue->enqueue($transaction);
					}*/
					$this->handleFailure($transaction, $failed);
					continue;
				}
			}
		}
		$this->isExecuting = false;
		echo "Finished queue execution\n";
		//$this->transactionQueue = null;
		$this->inventories = [];
		$this->lastExecution = microtime(true);
		$this->hasExecuted = true;
		return $failed;
	}
}