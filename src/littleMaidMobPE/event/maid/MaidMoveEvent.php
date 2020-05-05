<?php

namespace littleMaidMobPE\event\maid;

use littleMaidMobPE\event\maid\MaidEvent;

use pocketmine\math\Vector3;

class MaidMoveEvent extends MaidEvent{

	protected $pos;

	function __construct(int $eid, Vector3 $pos){
		$this->eid = $eid;
		$this->pos = $pos;
	}

	public function getTo(){
		return $this->pos;
	}
}