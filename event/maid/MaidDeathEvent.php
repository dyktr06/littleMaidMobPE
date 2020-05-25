<?php

namespace littleMaidMobPE\event\maid;

use littleMaidMobPE\event\maid\MaidEvent;

class MaidDeathEvent extends MaidEvent{

	protected $drop;

	function __construct(int $eid, array $drop){
		$this->eid = $eid;
		$this->drop = $drop;
	}

	public function getDrop(): array{
		return $this->drop;
	}
}