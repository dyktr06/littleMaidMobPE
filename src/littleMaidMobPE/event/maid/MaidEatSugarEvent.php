<?php

namespace littleMaidMobPE\event\maid;

use littleMaidMobPE\event\maid\MaidEvent;

class MaidEatSugarEvent extends MaidEvent{

	function __construct(int $eid){
		$this->eid = $eid;
	}
}