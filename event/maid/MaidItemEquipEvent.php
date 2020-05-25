<?php

namespace littleMaidMobPE\event\maid;

use littleMaidMobPE\event\maid\MaidEvent;

use pocketmine\item\Item;

class MaidItemEquipEvent extends MaidEvent{

	function __construct(int $eid, Item $item){
		$this->eid = $eid;
		$this->item = $item;
	}

	public function getItem(): Item{
		return $this->item;
	}

}