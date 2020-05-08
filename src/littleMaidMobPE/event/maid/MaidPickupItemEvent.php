<?php

namespace littleMaidMobPE\event\maid;

use littleMaidMobPE\event\maid\MaidEvent;

use pocketmine\entity\object\ItemEntity;

class MaidPickupItemEvent extends MaidEvent{

	protected $itementity;

	function __construct(int $eid, ItemEntity $itementity){
		$this->eid = $eid;
		$this->itementity = $itementity;
	}

	public function getEntity(){
		return $this->itementity;
	}

	public function getItemEntity(){
		return $this->itementity;
	}

	public function getItem(){
		return $this->itementity->getItem();
	}
}