<?php

namespace littleMaidMobPE\event\maid;

use littleMaidMobPE\event\maid\MaidEvent;

use pocketmine\entity\Entity;

class MaidDamageEvent extends MaidEvent{

	protected $entity;
	protected $damage;
	protected $finaldamage;

	function __construct(int $eid, Entity $entity, float $damage, float $finaldamage){
		$this->eid = $eid;
		$this->entity = $entity;
		$this->damage = $damage;
		$this->finaldamage = $finaldamage;
	}

	public function getDamager(): Entity{
		return $this->entity;
	}

	public function getBaseDamage(): float{
		return $this->damage;
	}

	public function getFinalDamage(): float{
		return $this->finaldamage;
	}
}