<?php

namespace littleMaidMobPE\event\maid;

use littleMaidMobPE\event\maid\MaidEvent;

use pocketmine\level\Level;
use pocketmine\math\Vector3;

class MaidSpawnEvent extends MaidEvent{

	protected $pos;
	protected $level;

	function __construct(int $eid, Vector3 $pos, Level $level){
		$this->eid = $eid;
		$this->pos = $pos;
		$this->level = $level;
	}

	public function getPosition(): Vector3{
		return $this->pos;
	}

	public function getLevel(): Level{
		return $this->level;
	}
}