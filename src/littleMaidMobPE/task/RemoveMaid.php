<?php

namespace littleMaidMobPE\task;

use pocketmine\scheduler\Task;
use pocketmine\plugin\Plugin;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\Server;

class RemoveMaid extends Task{
	private $owner;

	function __construct(Plugin $owner, int $eid){
		$this->owner = $owner;
		$this->eid = $eid;
		$this->Maid = $this->owner->Maid;
	}

	function getOwner(): Plugin{
		return $this->owner;
	}

	function onRun(int $currentTick){
		$eid = $this->eid;
		if(!$this->getOwner()->isMaid($eid))
			return false;
		
		$packet = new ActorEventPacket();
		$packet->entityRuntimeId = $eid;
		$packet->event = 61;
		foreach($this->getOwner()->getServer()->getOnlinePlayers() as $players){
			$players->dataPacket($packet);
		}
		
		$packet = new RemoveActorPacket();
		$packet->entityUniqueId = $eid;
		foreach($this->getOwner()->getServer()->getOnlinePlayers() as $players){
			$players->dataPacket($packet);
		}
		$this->getOwner()->getServer()->removePlayerListData($this->Maid->Maiddata[$eid]["uuid"], $this->getOwner()->getServer()->getOnlinePlayers());
		unset($this->Maid->Maiddata[$eid]);
		unset($this->getOwner()->eid[$eid]);
	}
}