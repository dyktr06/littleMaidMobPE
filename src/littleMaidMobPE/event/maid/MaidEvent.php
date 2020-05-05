<?php

namespace littleMaidMobPE\event\maid;

use pocketmine\event\Event;

abstract class MaidEvent extends Event{

	protected $eid;

	public function getMaidEntityRuntimeId(){
		return $this->eid;
	}
}