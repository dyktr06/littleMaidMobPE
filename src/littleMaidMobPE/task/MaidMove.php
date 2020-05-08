<?php

namespace littleMaidMobPE\task;

use pocketmine\entity\Entity;
use pocketmine\entity\object\ItemEntity;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;
use pocketmine\plugin\Plugin;
use pocketmine\Player;
use pocketmine\Server;

class MaidMove extends Task{
	private $owner;

	function __construct(Plugin $owner, int $eid, float $x, float $y, float $z, float $yaw, float $pitch, int $target){
		$this->owner = $owner;
		$this->eid = $eid;
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
		$this->yaw = $yaw;
		$this->pitch = $pitch;
		$this->target = $target;
		$this->Maid = $this->owner->Maid;
	}

	function getOwner(): Plugin{
		return $this->owner;
	}

	function onRun(int $currentTick){
		if(!$this->getOwner()->isMaid($this->eid))
			return false;
		
		$eid = $this->eid;
		$level = $this->Maid->Maiddata[$eid]["level"];
		$x = $this->x;
		$y = $this->y;
		$z = $this->z;
		$yaw = $this->yaw;
		$pitch = $this->pitch;
		$target = $this->target;
		$playername = $this->Maid->Maiddata[$eid]["playername"];
		$mode = $this->Maid->Maiddata[$eid]["mode"];
		$speed = $this->Maid->Maiddata[$eid]["speed"] / 20;
		
		if($y <= 0) 
			$this->getOwner()->MaidDeath($eid);
		
		if($this->Maid->Maiddata[$eid]["delaytime"] > 0)
			$this->Maid->Maiddata[$eid]["delaytime"] -= 1;
		
		$this->Maid->Maiddata[$eid]["atktime"] += 1;
		
		if($target === 0 and $mode === 0){ //RandomWalk
			if($this->Maid->Maiddata[$eid]["walkcount"] >= 30){
				$this->Maid->Maiddata[$eid]["randomwalk"] = mt_rand(1,10);
				$this->Maid->Maiddata[$eid]["walkcount"] = 0;
				if($this->Maid->Maiddata[$eid]["speed"] > $this->getOwner()->MaidSpeed){
					$this->Maid->Maiddata[$eid]["speed"] = $this->getOwner()->MaidSpeed;
				}
			}
			
			$randomwalk = $this->Maid->Maiddata[$eid]["randomwalk"];
			switch($randomwalk){
				case 1:
					$x += $speed;
					$yaw = 270;
					break;
				case 2:
					$x -= $speed;
					$yaw = 90;
					break;
				case 3:
					$z += $speed;
					$yaw = 360;
					break;
				case 4:
					$z -= $speed;
					$yaw = 180;
					break;
				case 5:
					$x += $speed;
					$z += $speed;
					$yaw = 315;
					break;
				case 6:
					$x += $speed;
					$z -= $speed;
					$yaw = 225;
					break;
				case 7:
					$x -= $speed;
					$z += $speed;
					$yaw = 45;
					break;
				case 8:
					$x -= $speed;
					$z -= $speed;
					$yaw = 135;
					break;
				default:
					$yaw = $yaw;
			}
			$this->Maid->Maiddata[$eid]["walkcount"]++;
			
			$y = $this->getOwner()->CalcYbyBlock($x, $y, $z, $level);
			
			$finalpos = new Vector3($x, $y, $z);
			$this->getOwner()->MaidMove($eid, $finalpos, $yaw, $pitch);
			
			$atktime = $this->Maid->Maiddata[$eid]["atktime"];
			$reatk = $this->Maid->Maiddata[$eid]["reatk"];
			if($this->getOwner()->isPlayerMaid($eid) and $atktime >= $reatk){
				$this->Maid->Maiddata[$eid]["atktime"] = 0;
				$this->getOwner()->SearchItemEntity($eid);
			}
			
			$target = $this->Maid->Maiddata[$eid]["target"];
			$this->getOwner()->getScheduler()->scheduleDelayedTask(new MaidMove($this->getOwner(), $eid, $x, $y, $z, $yaw, $pitch, $target), 1);
		}else{
			if($this->Maid->Maiddata[$eid]["speed"] > $this->getOwner()->MaidSpeed)
				$this->Maid->Maiddata[$eid]["speed"] = $this->getOwner()->MaidSpeed;
			
			$player = $this->getOwner()->getServer()->getPlayer($playername);
			if(!$player instanceof Player)
				return false;
			
			$targetentity = $level->getEntity($target);
			if($targetentity != null){
				$px = $targetentity->x;
				$py = $targetentity->y;
				$pz = $targetentity->z;
				$level = $targetentity->getLevel();
			}else{
				if($mode === 0){
					$this->Maid->Maiddata[$eid]["target"] = 0;
					$this->getOwner()->getScheduler()->scheduleDelayedTask(new MaidMove($this->getOwner(), $eid, $x, $y, $z, $yaw, $pitch, 0), 1);
					return false;
				}else{
					$px = $player->x;
					$py = $player->y;
					$pz = $player->z;
					$level = $player->getLevel();
					$this->Maid->Maiddata[$eid]["target"] = $player->getid();
					$targetentity = $level->getEntity($this->Maid->Maiddata[$eid]["target"]);
				}
			}
			
			$pos = new Vector3($x, $y + 1.62, $z);
			$targetpos = new Vector3($px, $py, $pz);
			$epx = $px - $x;
			$epy = $py - $y;
			$epz = $pz - $z;
			$playerdistance = $this->Maid->Maiddata[$eid]["playerdistance"];
			$enemydistance = $this->Maid->Maiddata[$eid]["enemydistance"];
			$searchdistance = $this->Maid->Maiddata[$eid]["searchdistance"];
			$speed = $this->Maid->Maiddata[$eid]["speed"] / 20;
			if(($target === $player->getid() and $targetpos->distance($pos) <= $playerdistance) or ($target !== $player->getid() and $targetpos->distance($pos) <= $enemydistance)){
				if($px > $x){
					$x += 0;
				}else{
					$x -= 0;
				}
				if($pz > $z){
					$z += 0;
				}else{
					$z -= 0;
				}
			}else{
				if($px > $x){
					$x += $speed;
				}else{
					$x -= $speed;
				}
				if($pz > $z){
					$z += $speed;
				}else{
					$z -= $speed;
				}
			}
			$yaw = ($yaw < 0) ? $yaw + 360 : rad2deg(atan2($epz, $epx)) - 90;
			
			$y = $this->getOwner()->CalcYbyBlock($x, $y, $z, $level);
			
			$checkpos = new Vector3($x, $y, $z);
			if($this->getOwner()->isNearMaid($eid, $checkpos) === true){ // TODO “K“–‚È”½”­
				$randx = mt_rand(1,3);
				if($randx === 1){
					$x -= $speed * 1;
				}elseif($randx === 2){
					$x += $speed * 1;
				}
				$randz = mt_rand(1,3);
				if($randz === 1){
					$z -= $speed * 1;
				}elseif($randz === 2){
					$z += $speed * 1;
				}
			}
			
			if($player->distance($pos) >= $searchdistance and $mode === 1 and $speed !== 0){
				$x = $player->x;
				$y = $player->y;
				$z = $player->z;
			}
			
			$finalpos = new Vector3($x, $y, $z);
			$this->getOwner()->MaidMove($eid, $finalpos, $yaw, $pitch);
			
			if($targetentity->distance($pos) >= $searchdistance and $target !== $player->getid())
				$this->Maid->Maiddata[$eid]["target"] = $player->getid();
			
			$this->Maid->Maiddata[$eid]["level"] = $level;
			$this->Maid->Maiddata[$eid]["time"] -= 1;
			$time = $this->Maid->Maiddata[$eid]["time"];
			$target = $this->Maid->Maiddata[$eid]["target"];
			if($time <= 0){
				$sugar = $this->Maid->Maiddata[$eid]["sugar_amount"];
				if($sugar > 0){
					$this->getOwner()->EatSugar($eid);
					$this->getOwner()->getScheduler()->scheduleDelayedTask(new MaidMove($this->getOwner(), $eid, $x, $y, $z, $yaw, $pitch, $target), 1);
				}else{
					$this->getOwner()->MaidReset($eid);
					$this->getOwner()->getScheduler()->scheduleDelayedTask(new MaidMove($this->getOwner(), $eid, $x, $y, $z, $yaw, $pitch, 0), 1);
				}
			}else{
				$this->getOwner()->getScheduler()->scheduleDelayedTask(new MaidMove($this->getOwner(), $eid, $x, $y, $z, $yaw, $pitch, $target), 1);
				$this->AttackMove($eid, $player, $targetentity);
			}
		}
	}

	function AttackMove(int $eid, Player $player, Entity $targetentity) {
		$atktime = $this->Maid->Maiddata[$eid]["atktime"];
		$reatk = $this->Maid->Maiddata[$eid]["reatk"];
		$atkrange = $this->Maid->Maiddata[$eid]["atkrange"];
		$hp = $this->Maid->Maiddata[$eid]["hp"];
		$maxhp = $this->Maid->Maiddata[$eid]["maxhp"];
		$targetpos = new Vector3($targetentity->x, $targetentity->y, $targetentity->z);
		if($targetentity->getid() !== $player->getid() and $targetpos->distance($this->getOwner()->getMaidPosition($eid)) <= $atkrange and $atktime >= $reatk){
			if($targetentity instanceof ItemEntity){
				if($this->Maid->Maiddata[$eid]["iteminhand"]->getid() !== 261)
					$this->getOwner()->MaidPickupItem($eid, $targetentity);
			}else{
				$this->getOwner()->MaidATK($eid, $targetentity);
			}
		}elseif($hp < $maxhp and $atktime >= $reatk){
			$this->getOwner()->EatSugar($eid);
		}elseif($targetentity->getid() === $player->getid() and $atktime >= $reatk){
			$this->Maid->Maiddata[$eid]["atktime"] = 0;
			$this->getOwner()->SearchItemEntity($eid);
		}
	}
}