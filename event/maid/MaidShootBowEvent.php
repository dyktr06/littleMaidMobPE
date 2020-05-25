<?php

namespace littleMaidMobPE\event\maid;

use littleMaidMobPE\event\maid\MaidEvent;

class MaidShootBowEvent extends MaidEvent{

	function __construct(int $eid){
		$this->eid = $eid;
	}
}