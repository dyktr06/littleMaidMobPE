<?php

namespace littleMaidMobPE\event\maid;

use littleMaidMobPE\event\maid\MaidEvent;

use pocketmine\Player;

class MaidContractEvent extends MaidEvent{

	protected $player;

	function __construct(int $eid, Player $player){
		$this->eid = $eid;
		$this->player = $player;
	}

	public function getPlayer(){
		return $this->player;
	}
}