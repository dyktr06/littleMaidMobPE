<?php

namespace littleMaidMobPE\projectile;

use littleMaidMobPE\Main;
use littleMaidMobPE\Maid;

use pocketmine\scheduler\Task;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\UUID;

class Arrow {

	private $main;
	private $maid;
	private $maideid;
	private $damage;
	private $speed;
	private $type;

	public function __construct(Main $main, Maid $maid, int $maideid, int $damage, float $speed, int $type) {
		$this->Main = $main;
		$this->Maid = $maid;
		$this->maideid = $maideid;
		$this->damage = $damage;
		$this->speed = $speed;
		$this->range = 40;
		$this->type = $type;
	}
	
	public function Shoot(): void{
		$damage = $this->damage;
		$range = $this->range;
		$speed = $this->speed;
		$maideid = $this->maideid;
		$yaw = $this->Maid->Maiddata[$maideid]["yaw"];
		$pitch = $this->Maid->Maiddata[$maideid]["pitch"];
		$level = $this->Maid->Maiddata[$maideid]["level"];
		$x = $this->Maid->Maiddata[$maideid]["x"];
		$y = $this->Maid->Maiddata[$maideid]["y"] + 0.9;
		$z = $this->Maid->Maiddata[$maideid]["z"];
		
		$packet = new AddActorPacket();
		$eid = mt_rand(100000, 10000000);
		$packet->entityUniqueId = $eid;
		$packet->entityRuntimeId = $eid;
		$packet->type = $this->type;
		$packet->position = new Vector3($x, $y, $z);
		$packet->yaw = $yaw;
		$packet->pitch = $pitch;
		@$flags |= 0 << Entity::DATA_FLAG_INVISIBLE;
		@$flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
		@$flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
		@$flags |= 0 << Entity::DATA_FLAG_IMMOBILE;
		$packet->metadata = [
			Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
			Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, ""],
			Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1],
 			Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 1],
			  	];
		foreach($level->getPlayers() as $players){
			$players->dataPacket($packet);
		}
		
		$this->Main->getScheduler()->scheduleDelayedTask(new ArrowMoving($this, $eid, $x, $y, $z, $level, $yaw, $pitch, $damage, $range, $speed), 2);
		
		$packet = new LevelSoundEventPacket;
		$packet->sound = LevelSoundEventPacket::SOUND_BOW;
		$packet->position = new Vector3($x, $y, $z);
		foreach($level->getPlayers() as $players){
			$players->dataPacket($packet);
		}
	}

	public function Remove(int $eid): void{
		$packet = new RemoveActorPacket();
		$packet->entityUniqueId = $eid;
		foreach($this->Main->getServer()->getOnlinePlayers() as $players){
			$players->dataPacket($packet);
		}
	}

	public function Move(int $eid, Vector3 $pos, Level $level): void{
		$packet = new MoveActorAbsolutePacket();
		$packet->entityRuntimeId = $eid;
		$packet->position = $pos;
		$packet->xRot = $pos->x;
		$packet->yRot = $pos->y;
		$packet->zRot = $pos->z;
		foreach($level->getPlayers() as $players){
			$players->dataPacket($packet);
		}
	}
}

class ArrowMoving extends Task{

	private $owner;
	private $eid;
	private $x;
	private $y;
	private $z;
	private $level;
	private $yaw;
	private $pitch;
	private $damage;
	private $range;
	private $speed;

	function __construct($owner, int $eid, float $x, float $y, float $z, Level $level, float $yaw, float $pitch, int $damage, int $range, float $speed){
		$this->owner = $owner;
		$this->eid = $eid;
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
		$this->level = $level;
		$this->yaw = $yaw;
		$this->pitch = $pitch;
		$this->damage = $damage;
		$this->range = $range;
		$this->speed = $speed;
	}

	function getOwner(){
		return $this->owner;
	}

	function onRun(int $currentTick){
		$level = $this->level;
		$eid = $this->eid;
		$x = $this->x;
		$y = $this->y;
		$z = $this->z;
		$pos = new Vector3($x, $y, $z);
		$blockid = $level->getBlock($pos)->getId();
		if($blockid === 0 and $y <= 256 and $y > 0){
			$yaw = $this->yaw;
			$pitch = $this->pitch;
			$damage = $this->damage;
			$range = $this->range;
			$speed = $this->speed;
			$plusy = -sin(deg2rad($pitch));
			$plusxz = cos(deg2rad($pitch));
			$plusx = -$plusxz * sin(deg2rad($yaw));
			$plusz = $plusxz * cos(deg2rad($yaw));
			$movedpos = new Vector3($x + $plusx * $speed, $y + $plusy * $speed, $z + $plusz * $speed);
			$this->getOwner()->Move($eid, $movedpos, $level);
			$hit = 0;
			foreach($level->getEntities() as $entities){
				if($movedpos->distance($entities) <= 2){
					$event = new EntityDamageEvent($entities, EntityDamageEvent::CAUSE_CUSTOM, $damage);
					$entities->attack($event);
					$hit++;
				}
			}
			if($hit > 0 or $range <= 0){
				$this->getOwner()->Remove($eid);
			}else{
				$range--;
				$this->getOwner()->Main->getScheduler()->scheduleDelayedTask(new ArrowMoving($this->getOwner(), $eid, $x + $plusx, $y + $plusy, $z + $plusz, $level, $yaw, $pitch, $damage, $range, $speed), 2);
			}
		}else{
			$this->getOwner()->Remove($eid);
		}
	}
}