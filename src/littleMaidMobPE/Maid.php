<?php

namespace littleMaidMobPE;

use littleMaidMobPE\Main;
use littleMaidMobPE\EventListener;
use littleMaidMobPE\event\maid\MaidArmorEquipEvent;
use littleMaidMobPE\event\maid\MaidAttackEvent;
use littleMaidMobPE\event\maid\MaidContractEvent;
use littleMaidMobPE\event\maid\MaidDamageEvent;
use littleMaidMobPE\event\maid\MaidDeathEvent;
use littleMaidMobPE\event\maid\MaidEatSugarEvent;
use littleMaidMobPE\event\maid\MaidItemEquipEvent;
use littleMaidMobPE\event\maid\MaidMoveEvent;
use littleMaidMobPE\event\maid\MaidPickupItemEvent;
use littleMaidMobPE\event\maid\MaidShootBowEvent;
use littleMaidMobPE\event\maid\MaidSpawnEvent;
use littleMaidMobPE\inventory\MaidInventory;
use littleMaidMobPE\projectile\Arrow;
use littleMaidMobPE\task\MaidMove;
use littleMaidMobPE\task\RemoveMaid;

use pocketmine\scheduler\Task;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\particle\HeartParticle;
use pocketmine\level\particle\ItemBreakParticle;
use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\UUID;

class Maid {

	private $main;

	public function __construct(Main $main) {
		$this->Main = $main;
		$this->MaidSize = $this->Main->MaidSize;
		$this->MaidSpeed = $this->Main->MaidSpeed;
		$this->Server = $this->Main->getServer();
	}

	public function Spawn(Vector3 $pos, Level $level, Skin $skin): void{
		$eid = mt_rand(100000, 10000000);
		$air = Item::get(0, 0, 1); //空アイテム
		$size = $this->MaidSize;
		
		$packet = new AddPlayerPacket();
		$packet->entityRuntimeId = $eid;
		$packet->username = "";
		$packet->uuid = UUID::fromRandom();
		$packet->position = $pos;
		$packet->yaw = 0;
		$packet->pitch = 0;
		$packet->item = $air;
		@$flags |= 0 << Entity::DATA_FLAG_INVISIBLE;
		@$flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
		@$flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
		@$flags |= 0 << Entity::DATA_FLAG_IMMOBILE;
		$packet->metadata = [
			Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
			Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, ""],
			Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1],
	 		Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, $size], // 大きさ
		  	];
		$this->Server->updatePlayerListData($packet->uuid, $packet->entityRuntimeId, "", $skin, "", $this->Server->getOnlinePlayers());
		foreach($this->Server->getOnlinePlayers() as $players){
			$players->dataPacket($packet);
		}
		
		$this->Maiddata[$eid] = [
			"name" => "",
			"playername" => "", //雇い主の名前
			"playerid" => 0,
			"iteminhand" => $air,
			"helmet" => $air,
			"chestplate" => $air,
			"leggings" => $air,
			"boots" => $air,
			"sugar_amount" => 0,
			"maxhp" => 20,
			"hp" => 20,
			"atk" => 1,
			"def" => 0,
			"atkrange" => 2, //攻撃範囲(リーチ)
			"delay" => 10, //次にダメージを与えられるまでの時間
			"delaytime" => 0,
			"atktime" => 0,
			"time" => 0, //雇用期間
			"reatk" => 20, //再攻撃までの時間
			"speed" => $this->MaidSpeed,
			"level" => $level,
			"x" => $pos->x,
			"y" => $pos->y,
			"z" => $pos->z,
			"yaw" => 0,
			"pitch" => 0,
			"move" => 0,
			"uuid" => $packet->uuid,
			"skin" => $skin,
			"size" => $size,
			"playerdistance" => 2.5,
			"enemydistance" => 2, // atkrangeと同じ
			"searchdistance" => 20,
			"walkcount" => 0,
			"randomwalk" => mt_rand(1,10),
			"mode" => 0,
			"target" => 0,
		];
		
		for($i = 0; $i <= 26; $i++){
			$this->Maiddata[$eid]["inventory"][$i]["id"] = 0;
			$this->Maiddata[$eid]["inventory"][$i]["damage"] = 0;
			$this->Maiddata[$eid]["inventory"][$i]["amount"] = 1;
		}
		
		$x = $this->Maiddata[$eid]["x"];
		$y = $this->Maiddata[$eid]["y"];
		$z = $this->Maiddata[$eid]["z"];
		$yaw = $this->Maiddata[$eid]["yaw"];
		$pitch = $this->Maiddata[$eid]["pitch"];
		$target = $this->Maiddata[$eid]["target"];
		$this->Main->eid[$eid] = $eid;
		$this->Main->getScheduler()->scheduleDelayedTask(new MaidMove($this->Main, $eid, $x, $y, $z, $yaw, $pitch, $target), 1);
		
		$event = new MaidSpawnEvent($eid, $pos, $level);
		$event->call();
	}

	public function ItemEquip(int $eid, Item $item): void{
		if(!$this->Main->isMaid($eid))
			return;
		
		$atk = $this->Main->getAtk($item);
		$itemid = $item->getid();
		$this->Maiddata[$eid]["atk"] = $atk;
		$this->Maiddata[$eid]["iteminhand"] = $item;
		
		$packet = new MobEquipmentPacket();
		$packet->entityRuntimeId = $eid;
		$packet->item = $item;
		$packet->inventorySlot = 0;
		$packet->hotbarSlot = 0;
		foreach($this->Server->getOnlinePlayers() as $players){
			$players->dataPacket($packet);
		}
		
		if($itemid === 261){ // 弓
			$this->Maiddata[$eid]["atkrange"] = 10;
			$this->Maiddata[$eid]["enemydistance"] = 10;
			$this->Maiddata[$eid]["reatk"] = 40;
		}else{
			$this->Maiddata[$eid]["atkrange"] = 2;
			$this->Maiddata[$eid]["enemydistance"] = 2;
			$this->Maiddata[$eid]["reatk"] = 20;
		}
		
		if($this->Main->isPlayerMaid($eid)){
			$playername = $this->Maiddata[$eid]["playername"];
			$player = $this->Server->getPlayer($playername);
			$this->Main->PlayerMaidStatusSave($player, $this->Maiddata[$eid]["playerid"]);
		}
		
		$event = new MaidItemEquipEvent($eid, $item);
		$event->call();
	}

	public function ArmorEquip(int $eid, Item $item, int $part): void{
		if(!$this->Main->isMaid($eid))
			return;
		
		$armorValues = [
			Item::LEATHER_TUNIC => 4,
			Item::LEATHER_PANTS => 3,
			Item::LEATHER_BOOTS => 2,
			
			Item::CHAIN_CHESTPLATE => 6,
			Item::CHAIN_LEGGINGS => 5,
			Item::CHAIN_BOOTS => 2,
			
			Item::GOLD_CHESTPLATE => 6,
			Item::GOLD_LEGGINGS => 4,
			Item::GOLD_BOOTS => 2,
			
			Item::IRON_CHESTPLATE => 7,
			Item::IRON_LEGGINGS => 6,
			Item::IRON_BOOTS => 3,
			
			Item::DIAMOND_CHESTPLATE => 9,
			Item::DIAMOND_LEGGINGS => 7,
			Item::DIAMOND_BOOTS => 4,
		];
		
		$itemid = $item->getid();
		$head = $this->Maiddata[$eid]["helmet"];
		$chest = $this->Maiddata[$eid]["chestplate"];
		$legs = $this->Maiddata[$eid]["leggings"];
		$boots = $this->Maiddata[$eid]["boots"];
		if($part === 1){
			$this->Maiddata[$eid]["chestplate"] = $item;
			$this->Maiddata[$eid]["def"] -= $armorValues[$chest->getid()] ?? 0;
			$this->Maiddata[$eid]["def"] += $armorValues[$itemid] ?? 0;
			$packet = new MobArmorEquipmentPacket();
			$packet->entityRuntimeId = $eid;
			$packet->head = $head;
			$packet->chest = $item;
			$packet->legs = $legs;
			$packet->feet = $boots;
			foreach($this->Server->getOnlinePlayers() as $players){
				$players->dataPacket($packet);
			}
		}elseif($part === 2){
			$this->Maiddata[$eid]["leggings"] = $item;
			$this->Maiddata[$eid]["def"] -= $armorValues[$legs->getid()] ?? 0;
			$this->Maiddata[$eid]["def"] += $armorValues[$itemid] ?? 0;
			$packet = new MobArmorEquipmentPacket();
			$packet->entityRuntimeId = $eid;
			$packet->head = $head;
			$packet->chest = $chest;
			$packet->legs = $item;
			$packet->feet = $boots;
			foreach($this->Server->getOnlinePlayers() as $players){
				$players->dataPacket($packet);
			}
		}elseif($part === 3){
			$this->Maiddata[$eid]["boots"] = $item;
			$this->Maiddata[$eid]["def"] -= $armorValues[$boots->getid()] ?? 0;
			$this->Maiddata[$eid]["def"] += $armorValues[$itemid] ?? 0;
			$packet = new MobArmorEquipmentPacket();
			$packet->entityRuntimeId = $eid;
			$packet->head = $head;
			$packet->chest = $chest;
			$packet->legs = $legs;
			$packet->feet = $item;
			foreach($this->Server->getOnlinePlayers() as $players){
				$players->dataPacket($packet);
			}
		}
		if($this->Main->isPlayerMaid($eid)){
			$playername = $this->Maiddata[$eid]["playername"];
			$player = $this->Server->getPlayer($playername);
			$this->Main->PlayerMaidStatusSave($player, $this->Maiddata[$eid]["playerid"]);
		}
		
		$event = new MaidArmorEquipEvent($eid, $item, $part);
		$event->call();
	}

	public function Attack(int $eid, Entity $target): void{
		if(!$this->Main->isMaid($eid))
			return;
		
		$atk = $this->Maiddata[$eid]["atk"];
		if($this->Maiddata[$eid]["iteminhand"]->getid() === 261){ // 弓
			$this->Maiddata[$eid]["atktime"] = 0;
			$arrow = new Arrow($this->Main, $this, $eid, $atk, 4, 80);
			$arrow->Shoot();
			
			$event = new MaidShootBowEvent($eid);
			$event->call();
			return;
		}
		
		$def = 0; // TODO
		$x = $this->Maiddata[$eid]["x"];
		$y = $this->Maiddata[$eid]["y"];
		$z = $this->Maiddata[$eid]["z"];
		$pos = new Vector3($x, $y, $z);
		$damage = $atk * (1 - ($def * 0.04));
		$finaldamage = ($damage > 1) ? $damage : 1;
		$ev = new EntityDamageEvent($target, EntityDamageEvent::CAUSE_CUSTOM, $finaldamage);
		$target->attack($ev);
		if($target->x - $x >= 0){
			$motionx = 1;
		}else{
			$motionx = -1;
		}
		if($target->z - $z >= 0){
			$motionz = 1;
		}else{
			$motionz = -1;
		}
		
		$this->Maiddata[$eid]["atktime"] = 0;
		
		$packet = new ActorEventPacket();
		$packet->entityRuntimeId = $eid;
		$packet->event = 4;
		foreach($this->Server->getOnlinePlayers() as $players){
			$players->dataPacket($packet);
		}
		
		if(!$ev->isCancelled()){
			$motion = (new Vector3($motionx, 0.6, $motionz))->normalize(); // ノックバック
			$target->setmotion($motion);
		}
		
		$event = new MaidAttackEvent($eid, $target, $damage, $finaldamage);
		$event->call();
	}

	public function Damaged(Entity $entity, int $eid, int $damage): void{
		if(!$this->Main->isMaid($eid))
			return;
		
		$delaytime = $this->Maiddata[$eid]["delaytime"];
		if($delaytime > 0)
			return;
		
		$delay = $this->Maiddata[$eid]["delay"];
		$this->Maiddata[$eid]["delaytime"] = $delay;
		$packet = new ActorEventPacket();
		$packet->entityRuntimeId = $eid;
		$packet->event = 2;
		foreach($this->Server->getOnlinePlayers() as $players){
			$players->dataPacket($packet);
		}
		$def = $this->Maiddata[$eid]["def"];
		$finaldamage = $damage * (1 - ($def * 0.04));
		$this->Maiddata[$eid]["hp"] = $this->Maiddata[$eid]["hp"] - $finaldamage;
		$hp = $this->Maiddata[$eid]["hp"];
		$speed = $this->Maiddata[$eid]["speed"];
		
		$event = new MaidDamageEvent($eid, $entity, $damage, $finaldamage);
		$event->call();
		
		if($hp < 1){
			$this->MaidDeath($eid);
		}else{
			if($this->Maiddata[$eid]["playername"] === ""){
				$this->Maiddata[$eid]["speed"] = ($speed < $this->MaidSpeed * 2) ? $speed * 2 : $speed;
			}else{
				$this->Maiddata[$eid]["target"] = $entity->getid();
				$this->Maiddata[$eid]["speed"] = ($speed === 0) ? $this->MaidSpeed : $speed;
			}
		}
	}

	public function Death(int $eid): void{
		if(!$this->Main->isMaid($eid))
			return;
		
		$this->Reset($eid);
		$level = $this->Maiddata[$eid]["level"];
		$pos = new Vector3($this->Maiddata[$eid]["x"], $this->Maiddata[$eid]["y"], $this->Maiddata[$eid]["z"]);
		$item = $this->Maiddata[$eid]["iteminhand"];
		$chest = $this->Maiddata[$eid]["chestplate"];
		$legs = $this->Maiddata[$eid]["leggings"];
		$boots = $this->Maiddata[$eid]["boots"];
		$sugar = Item::get($this->Main->config->get("Control"), 0, $this->Maiddata[$eid]["sugar_amount"]);
		$inv = array($item, $chest, $legs, $boots, $sugar);
		for($i = 0; $i <= 26; $i++){
			$invitem = Item::get($this->Maiddata[$eid]["inventory"][$i]["id"], $this->Maiddata[$eid]["inventory"][$i]["damage"], $this->Maiddata[$eid]["inventory"][$i]["amount"]);
			array_push($inv, $item);
		}
		$y = -sin(deg2rad($this->Maiddata[$eid]["pitch"]));
		$xz = cos(deg2rad($this->Maiddata[$eid]["pitch"]));
		$x = -$xz * sin(deg2rad($this->Maiddata[$eid]["yaw"]));
		$z = $xz * cos(deg2rad($this->Maiddata[$eid]["yaw"]));
		$motion = new Vector3($x,$y - 0.4,$z);
		foreach($inv as $drop){
			$level->dropItem($pos, $drop, $motion);
		}
		
		$packet = new ActorEventPacket();
		$packet->entityRuntimeId = $eid;
		$packet->event = 3;
		foreach($this->Server->getOnlinePlayers() as $players){
			$players->dataPacket($packet);
		}
		
		$this->Main->getScheduler()->scheduleDelayedTask(new RemoveMaid($this->Main, $eid), 10);
		
		$event = new MaidDeathEvent($eid, $inv);
		$event->call();
	}

	public function Contract(Player $player, int $eid): void{
		if(!$this->Main->isMaid($eid))
			return;
		
		$maiditem = $this->Maiddata[$eid]["iteminhand"];
		$head = $this->Maiddata[$eid]["helmet"];
		$chest = $this->Maiddata[$eid]["chestplate"];
		$legs = $this->Maiddata[$eid]["leggings"];
		$boots = $this->Maiddata[$eid]["boots"];
		$sugar = $this->Maiddata[$eid]["sugar_amount"];
		$name = $player->getName();
		$this->Main->data[$name]["MaidCount"] += 1;
		$player_maid_count = $this->Main->data[$name]["MaidCount"];
		$defaulttime = 1200 * 20;
		$skin = $this->Maiddata[$eid]["skin"];
		$skinid = base64_encode($skin->getSkinId());
		$skindata = base64_encode($skin->getSkinData());
		$capedata = base64_encode($skin->getCapeData());
		$geometryname = base64_encode($skin->getGeometryName());
		$geometrydata = base64_encode($skin->getGeometryData());
		$this->Main->data[$name][$player_maid_count] = [
			"name" => "",
			"playerid" => $player_maid_count,
			"spawn" => 1,
			"eid" => $eid,
			"iteminhand" => $maiditem->getid(),
			"iteminhand_damage" => $maiditem->getdamage(),
			"helmet" => $head->getid(),
			"helmet_damage" => $head->getdamage(),
			"chestplate" => $chest->getid(),
			"chestplate_damage" => $chest->getdamage(),
			"leggings" => $legs->getid(),
			"leggings_damage" => $legs->getdamage(),
			"boots" => $boots->getid(),
			"boots_damage" => $boots->getdamage(),
			"sugar_amount" => $sugar,
			"maxhp" => 20,
			"hp" => 20,
			"time" => $defaulttime,
			"skinid" => $skinid,
			"skindata" => $skindata,
			"capedata" => $capedata,
			"geometryname" => $geometryname,
			"geometrydata" => $geometrydata,
			];
		
		for($i = 0; $i <= 26; $i++){
			$this->data[$name][$player_maid_count]["inventory"][$i]["id"] = $this->Maiddata[$eid]["inventory"][$i]["id"];
			$this->data[$name][$player_maid_count]["inventory"][$i]["damage"] = $this->Maiddata[$eid]["inventory"][$i]["damage"];
			$this->data[$name][$player_maid_count]["inventory"][$i]["amount"] = $this->Maiddata[$eid]["inventory"][$i]["amount"];
		}
		
		$this->Maiddata[$eid]["playerid"] = $player_maid_count;
		$this->Maiddata[$eid]["playername"] = $name;
		$this->Maiddata[$eid]["target"] = $player->getid();
		$this->Maiddata[$eid]["mode"] = 1;
		$this->Maiddata[$eid]["time"] = $defaulttime;
		$level = $player->getLevel();
		$particle = new HeartParticle(new Vector3($this->Maiddata[$eid]["x"], $this->Maiddata[$eid]["y"] + 1.5, $this->Maiddata[$eid]["z"]));
		$level->addParticle($particle);
		
		$event = new MaidContractEvent($eid, $player);
		$event->call();
	}

	public function EatSugar(int $eid): void{
		if(!$this->Main->isMaid($eid))
			return;
		
		$this->Maiddata[$eid]["atktime"] = 0;
				
		$this->Maiddata[$eid]["time"] += 1200 * 20;
		if($this->Maiddata[$eid]["time"] >= 1200 * 7 * 20){
			$this->Maiddata[$eid]["time"] = 1200 * 7 * 20;
		}
		
		if($this->Maiddata[$eid]["maxhp"] > $this->Maiddata[$eid]["hp"]){
			$this->Maiddata[$eid]["hp"] = $this->Maiddata[$eid]["hp"] + 1;
		}
		
		$particle = new ItemBreakParticle(new Vector3($this->Maiddata[$eid]["x"], $this->Maiddata[$eid]["y"] + 1, $this->Maiddata[$eid]["z"]), Item::get($this->Main->config->get("Control"), 0, 1));
		$this->Maiddata[$eid]["level"]->addParticle($particle);
		
		$event = new MaidEatSugarEvent($eid);
		$event->call();
	}

	public function Reset(int $eid): void{
		if(!$this->Main->isMaid($eid))
			return;
		
		$name = $this->Maiddata[$eid]["playername"];
		$playerid = $this->Maiddata[$eid]["playerid"];
		if($name !== "" and $playerid !== ""){
			unset($this->Main->data[$name][$playerid]);
		}
		
		$this->Maiddata[$eid]["target"] = 0;
		$this->Maiddata[$eid]["playername"] = "";
		$this->Maiddata[$eid]["playerid"] = "";
		$this->Maiddata[$eid]["mode"] = 0;
		$this->Maiddata[$eid]["time"] = 0;
		$this->Maiddata[$eid]["speed"] = $this->MaidSpeed;
	}

	public function Move(int $eid, Vector3 $pos, float $yaw, float $pitch): void{
		if(!$this->Main->isMaid($eid))
			return;
		
		$x = $pos->x;
		$y = $pos->y;
		$z = $pos->z;
		$packet = new MovePlayerPacket();
		$packet->entityRuntimeId = $eid;
		$packet->position = new Vector3($x, $y + 1.62, $z);
		$packet->pitch = $pitch;
		$packet->yaw = $yaw;
		$packet->headYaw = $yaw;
		foreach($this->Server->getOnlinePlayers() as $players){
			$players->dataPacket($packet);
		}
		
		$this->Maiddata[$eid]["x"] = $x;
		$this->Maiddata[$eid]["y"] = $y;
		$this->Maiddata[$eid]["z"] = $z;
		$this->Maiddata[$eid]["yaw"] = $yaw;
		$this->Maiddata[$eid]["pitch"] = $pitch;
		
		$event = new MaidMoveEvent($eid, $pos);
		$event->call();
	}

	public function PickupItem(int $eid, ItemEntity $target): void{
		if(!$this->Main->isMaid($eid))
			return;
		
		$this->Maiddata[$eid]["atktime"] = 0;
		
		$item = $target->getItem();
		$this->addItem($eid, $item);
		$target->kill();
		
		$event = new MaidPickupItemEvent($eid, $target);
		$event->call();
	}

	public function addItem(int $eid, Item $item): void{
		if(!$this->Main->isMaid($eid))
			return;
		
		for($i = 0; $i <= 26; $i++){
			$invid = $this->Maiddata[$eid]["inventory"][$i]["id"];
			$invdamage = $this->Maiddata[$eid]["inventory"][$i]["damage"];
			$invamount = $this->Maiddata[$eid]["inventory"][$i]["amount"];
			if($invid === 0 or $invamount === 0){
				$this->Maiddata[$eid]["inventory"][$i]["id"] = $item->getId();
				$this->Maiddata[$eid]["inventory"][$i]["damage"] = $item->getDamage();
				$this->Maiddata[$eid]["inventory"][$i]["amount"] = $item->getCount();
				break;
			}elseif($invid === $item->getId() and $invdamage === $item->getDamage() and $invamount + $item->getCount() <= $item->getMaxStackSize()){
				$this->Maiddata[$eid]["inventory"][$i]["id"] = $item->getId();
				$this->Maiddata[$eid]["inventory"][$i]["damage"] = $item->getDamage();
				$this->Maiddata[$eid]["inventory"][$i]["amount"] = $invamount + $item->getCount();
				break;
			}
		}
	}

	public function OpenInventory(Player $player, int $eid): void{
		if(!$this->Main->isMaid($eid))
			return;

		$inventory = new MaidInventory($player, $eid, $this);
		$player->addWindow($inventory);

		$x = $player->getFloorX();
		$y = $player->getFloorY() + 3;
		$z = $player->getFloorZ();
		
		$block = Block::get(Block::CHEST, 2, new Position($x, $y, $z));
		$player->getLevel()->sendBlocks([$player], [$block]);

		$nbt = new CompoundTag();
		$nbt->setString("id", "Chest");
		$nbt->setInt("x", $x);
		$nbt->setInt("y", $y);
		$nbt->setInt("z", $z);
		$nbt->setString("CustomName", "メイドさんのインベントリ");

		$stream = new NetworkLittleEndianNBTStream();
		$packet = new BlockActorDataPacket();
		$packet->x = $x;
		$packet->y = $y;
		$packet->z = $z;
		$packet->namedtag = $stream->write($nbt);
		$player->dataPacket($packet);

		$holder = $inventory->getHolder();

		$packet = new ContainerOpenPacket();
		$packet->windowId = $player->getWindowId($inventory);
		$packet->type = $inventory->getNetworkType();
		$packet->entityUniqueId = -1;
		$packet->x = $holder->getFloorX();
		$packet->y = $holder->getFloorY();
		$packet->z = $holder->getFloorZ();
		$player->dataPacket($packet);
		$inventory->sendContents($player);		
	}

	public function CloseInventory(Player $player, int $eid): void{
		if(!$this->Main->isMaid($eid))
			return;

		$inventory = new MaidInventory($player, $eid, $this);
		
		$packet = new ContainerClosePacket();
        	$packet->windowId = $player->getWindowId($inventory);
        	$player->dataPacket($packet);

		$block = Block::get(Block::AIR, 0, new Position($player->getFloorX(), $player->getFloorY() + 3, $player->getFloorZ()));
		$player->getLevel()->sendBlocks([$player], [$block]);
	}

	public function Redisplay(int $eid, Player $player): void{
		if(!$this->Main->isMaid($eid))
			return;
		
		$packet = new RemoveActorPacket();
		$packet->entityUniqueId = $eid;
		$this->Server->removePlayerListData($this->Maiddata[$eid]["uuid"], $this->Server->getOnlinePlayers());
		$player->dataPacket($packet);
		
		$maidname = $this->Maiddata[$eid]["name"];
		$x = $this->Maiddata[$eid]["x"];
		$y = $this->Maiddata[$eid]["y"];
		$z = $this->Maiddata[$eid]["z"];
		$yaw = $this->Maiddata[$eid]["yaw"];
		$pitch = $this->Maiddata[$eid]["pitch"];
		$item = $this->Maiddata[$eid]["iteminhand"];
		$head = $this->Maiddata[$eid]["helmet"];
		$chest = $this->Maiddata[$eid]["chestplate"];
		$legs = $this->Maiddata[$eid]["leggings"];
		$boots = $this->Maiddata[$eid]["boots"];
		
		$packet = new AddPlayerPacket();
		$packet->entityRuntimeId = $eid;
		$packet->uuid = UUID::fromRandom();
		$packet->username = $maidname;
		$packet->position = new Vector3($x, $y, $z);
		$packet->yaw = $yaw;
		$packet->pitch = $pitch;
		$packet->item = $item;
		@$flags |= 0 << Entity::DATA_FLAG_INVISIBLE;
		@$flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
		@$flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
		@$flags |= 0 << Entity::DATA_FLAG_IMMOBILE;
		$packet->metadata = [
			Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
			Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $maidname],
			Entity::DATA_FLAG_NO_AI => [Entity::DATA_TYPE_BYTE, 1],
		  	Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1],
			Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, $this->Maiddata[$eid]["size"]],//大きさ
			];
		$player->dataPacket($packet);
		
		$skin = $this->Maiddata[$eid]["skin"];
		$this->Server->updatePlayerListData($packet->uuid, $packet->entityRuntimeId, $maidname, $skin, "", $this->Server->getOnlinePlayers());
		
		$packet2 = new MobEquipmentPacket();
		$packet2->entityRuntimeId = $eid;
		$packet2->item = $item;
		$packet2->inventorySlot = 0;
		$packet2->hotbarSlot = 0;
		$player->dataPacket($packet2);
		
		$packet3 = new MobArmorEquipmentPacket();
		$packet3->entityRuntimeId = $eid;
		$packet3->head = $head;
		$packet3->chest = $chest;
		$packet3->legs = $legs;
		$packet3->feet = $boots;
		$player->dataPacket($packet3);
	}

	public function PlayerMaidSpawn(Player $player): void{
		$name = $player->getname();
		$maidcount = $this->Main->data[$name]["MaidCount"];
		for($i = 1; $i <= $maidcount; $i++){
			if(isset($this->Main->data[$name][$i])){
				if($this->Main->data[$name][$i]["spawn"] === 0){
					$eid = mt_rand(100000, 10000000);
					$maidname = $this->Main->data[$name][$i]["name"];
					$iteminhand = Item::get($this->Main->data[$name][$i]["iteminhand"], $this->Main->data[$name][$i]["iteminhand_damage"], 1);
					$head = Item::get($this->Main->data[$name][$i]["helmet"], $this->Main->data[$name][$i]["helmet_damage"], 1);
					$chest = Item::get($this->Main->data[$name][$i]["chestplate"], $this->Main->data[$name][$i]["chestplate_damage"], 1);
					$legs = Item::get($this->Main->data[$name][$i]["leggings"], $this->Main->data[$name][$i]["leggings_damage"], 1);
					$boots = Item::get($this->Main->data[$name][$i]["boots"], $this->Main->data[$name][$i]["boots_damage"], 1);
					$sugar = $this->Main->data[$name][$i]["sugar_amount"];
					$maxhp = $this->Main->data[$name][$i]["maxhp"];
					$hp = $this->Main->data[$name][$i]["hp"];
					$time = $this->Main->data[$name][$i]["time"];
					$size = $this->MaidSize;
					
					$packet = new AddPlayerPacket();
					$packet->entityRuntimeId = $eid;
					$packet->username = $maidname;
					$packet->uuid = UUID::fromRandom();
					$packet->position = new Vector3($player->x, $player->y + 1, $player->z);
					$packet->yaw = 0;
					$packet->pitch = 0;
					$packet->item = $iteminhand;
					@$flags |= 0 << Entity::DATA_FLAG_INVISIBLE;
					@$flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
					@$flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
					@$flags |= 0 << Entity::DATA_FLAG_IMMOBILE;
					$packet->metadata = [
						Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
						Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $maidname],
						Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1],
			 			Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, $size],//大きさ
						  	];
					
					$geometryJsonEncoded = base64_decode($this->Main->data[$name][$i]["geometrydata"]);
					if($geometryJsonEncoded !== "") $geometryJsonEncoded = \json_encode(\json_decode($geometryJsonEncoded));
					
				 	$skin = new Skin(base64_decode($this->Main->data[$name][$i]["skinid"]), base64_decode($this->Main->data[$name][$i]["skindata"]), base64_decode($this->Main->data[$name][$i]["capedata"]), base64_decode($this->Main->data[$name][$i]["geometryname"]), $geometryJsonEncoded);
					$this->Server->updatePlayerListData($packet->uuid, $packet->entityRuntimeId, "", $skin, "", $this->Server->getOnlinePlayers());
					foreach($this->Server->getOnlinePlayers() as $players){
						$players->dataPacket($packet);
					}
					
					$this->Maiddata[$eid] = [
					"name" => $maidname,
					"playername" => $name, //雇い主の名前
					"playerid" => $i,
					"iteminhand" => $iteminhand,
					"helmet" => $head,
					"chestplate" => $chest,
					"leggings" => $legs,
					"boots" => $boots,
					"sugar_amount" => $sugar,
					"maxhp" => $maxhp,
					"hp" => $hp,
					"atk" => 1,
					"def" => 0,
					"atkrange" => 2, //攻撃範囲(リーチ)
					"delay" => 10, //次にダメージを与えられるまでの時間
					"delaytime" => 0,
					"atktime" => 0,
					"time" => $time, //雇用期間
					"reatk" => 20, //再攻撃までの時間
					"speed" => $this->MaidSpeed,
					"level" => $player->getLevel(),
					"x" => $player->x,
					"y" => $player->y,
					"z" => $player->z,
					"yaw" => 0,
					"pitch" => 0,
					"move" => 0,
					"uuid" => $packet->uuid,
					"skin" => $skin,
					"size" => $size,
					"playerdistance" => 2.5,
					"enemydistance" => 2, // atkrangeと同じ
					"searchdistance" => 20,
					"walkcount" => 0,
					"randomwalk" => mt_rand(1,10),
					"mode" => 1,
					"target" => $player->getid(),
					];
					
					for($j = 0; $j <= 26; $j++){
						$this->Maiddata[$eid]["inventory"][$j]["id"] = $this->Main->data[$name][$i]["inventory"][$j]["id"];
						$this->Maiddata[$eid]["inventory"][$j]["damage"] = $this->Main->data[$name][$i]["inventory"][$j]["damage"];
						$this->Maiddata[$eid]["inventory"][$j]["amount"] = $this->Main->data[$name][$i]["inventory"][$j]["amount"];
					}
					
					$this->Main->data[$name][$i]["spawn"] = 1;
					$this->Main->data[$name][$i]["eid"] = $eid;
					$this->Main->eid[$eid] = $eid;
					$this->ArmorEquip($eid, $chest, 1);
					$this->ArmorEquip($eid, $legs, 2);
					$this->ArmorEquip($eid, $boots, 3);
					$this->ItemEquip($eid, $iteminhand);
					$x = $this->Maiddata[$eid]["x"];
					$y = $this->Maiddata[$eid]["y"];
					$z = $this->Maiddata[$eid]["z"];
					$yaw = $this->Maiddata[$eid]["yaw"];
					$pitch = $this->Maiddata[$eid]["pitch"];
					$target = $this->Maiddata[$eid]["target"];
					$this->Main->getScheduler()->scheduleDelayedTask(new MaidMove($this->Main, $eid, $x, $y, $z, $yaw, $pitch, $target), 1);
					
					$event = new MaidSpawnEvent($eid, $packet->position, $player->getLevel());
					$event->call();
				}
			}
		}
	}
}