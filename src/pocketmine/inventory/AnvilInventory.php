<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\inventory;

use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\Player;

class AnvilInventory extends TemporaryInventory{
	
	const TARGET = 0;
	const SACRIFICE = 1;
	const RESULT = 2;
	
	
	public function __construct(Position $pos){
		parent::__construct(new FakeBlockMenu($this, $pos), InventoryType::get(InventoryType::ANVIL));
	}

	/**
	 * @return FakeBlockMenu
	 */
	public function getHolder(){
		return $this->holder;
	}
	
	public function getResultSlotIndex(){
		return self::RESULT;
	}
	
	public function onRename(Player $player) : bool{
		$item = $this->getItem(self::RESULT);
		if($player->getExpLevel() > $item->getRepairCost()){
			$player->setExpLevel($player->getExpLevel() - $item->getRepairCost());
			return true;
		}
		return false;
	}
	
	public function onProcessSlotChange(Transaction $transaction){
		//If ANY slot in the anvil changes, we need to recalculate the anvil contents
		if($transaction->getSlot() === $this->getResultSlotIndex()){
			if($transaction->getTargetItem()->getId() === Item::AIR){
				//result slot changed - an item removed from the anvil
				//returning true tells the transaction queue to handle this transaction the normal way
				if($this->getItem(self::SACRIFICE)->getId() !== Item::AIR){
					//calculate repair item cost
					$durabilityDifference = $this->getItem(self::RESULT)->getDamage() - $this->getItem(self::TARGET)->getDamage();
					//Potential for divide by zero here. TODO: fix
					$materialsUsed = ceil(($durabilityDifference / (int) $this->getItem(self::RESULT)->getMaxDurability()) * 4);
					
					if($this->getItem(self::SACRIFICE)->getCount() >= $materialsUsed){
						//Enough materials to go ahead
						//TODO: finish
					}
					
				}
				$this->clear(self::TARGET);
				
				return true;
			}else{
				//result slot changed some other way
				//TODO: check count changes
				$this->setItem(self::RESULT, $transaction->getTargetItem(), false);
				return false;
			}
		}else{
			if($target->getId() === Item::AIR){
				//item removed from either the sacrifice slot or the target slot
				$this->clear(self::RESULT);
			}else{
				//slot changed in some other way - maybe a count change?
			}
			
		}
		
		
		if($index === $this->getResultSlotIndex() and $this->getItem($index)->getId() !== Item::AIR and $target->getId() === Item::AIR){
			//Item was removed from the anvil's result slot, recalculate the other slots
			if($this->getItem(self::RESULT)->deepEquals($this->getItem(self::TARGET), false, false, true)){
				//Enchantment added or item repaired
				//TODO: Check if the material in the sacrifice slot can actually be used to repair specified item
				
			}else{
				
			}
			
			
		}
	}
	

	public function onClose(Player $who){
		$who->updateExperience();
		parent::onClose($who);

		$this->getHolder()->getLevel()->dropItem($this->getHolder()->add(0.5, 0.5, 0.5), $this->getItem(0));
		$this->getHolder()->getLevel()->dropItem($this->getHolder()->add(0.5, 0.5, 0.5), $this->getItem(1));

		$this->clear(0);
		$this->clear(1);
		$this->clear(2);
	}

}