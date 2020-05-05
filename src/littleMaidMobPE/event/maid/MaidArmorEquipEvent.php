<?php

namespace littleMaidMobPE\event\maid;

use littleMaidMobPE\event\maid\MaidEvent;

use pocketmine\item\Item;

class MaidArmorEquipEvent extends MaidEvent{

	protected $item;
	protected $part;

	function __construct(int $eid, Item $item, int $part){
		$this->eid = $eid;
		$this->item = $item;
		$this->part = $part;
	}

	public function getItem(){
		return $this->item;
	}

	public function getPart(){
		return $this->part;
	}
}