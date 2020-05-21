<?php

namespace littleMaidMobPE\event\maid;

use littleMaidMobPE\event\maid\MaidEvent;

use pocketmine\entity\object\ItemEntity;
use pocketmine\item\Item;

class MaidPickupItemEvent extends MaidEvent{

	protected $itementity;

	function __construct(int $eid, ItemEntity $itementity){
		$this->eid = $eid;
		$this->itementity = $itementity;
	}

	public function getEntity(): ItemEntity{
		return $this->itementity;
	}

	public function getItemEntity(): ItemEntity{
		return $this->itementity;
	}

	public function getItem(): Item{
		return $this->itementity->getItem();
	}
}