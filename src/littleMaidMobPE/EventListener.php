<?php

namespace littleMaidMobPE;

use littleMaidMobPE\Main;
use littleMaidMobPE\Maid;
use littleMaidMobPE\form\MaidSugarForm;
use littleMaidMobPE\form\MaidStatusForm;

use pocketmine\event\Listener;
use pocketmine\scheduler\Task;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\UUID;

class EventListener implements Listener{

	public $maid;
	public $main;

	public function __construct(Main $main, Maid $maid) {
		$this->Main = $main;
		$this->Maid = $maid;
	}

	public function onReceive(DataPacketReceiveEvent $event){
		$packet = $event->getPacket();
		$player = $event->getPlayer();
		$level = $player->getLevel();
		$name = $player->getName();
		if($packet instanceof InventoryTransactionPacket){
			if(InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY){
				$eid = $packet->trData->entityRuntimeId ?? null;
				if($eid === null)
					return false;
				
				if($this->Main->isMaid($eid)){
					$maidplayername = $this->Maid->Maiddata[$eid]["playername"]; // メイドさんが契約してるプレイヤーの名前
					$item = $player->getInventory()->getItemInHand();
					$itemid = $item->getid();
					$contract = $this->Main->config->get("Contract");
					$control = $this->Main->config->get("Control");
					$instruction = $this->Main->config->get("Instruction");
					if($itemid === $contract and $maidplayername === ""){
						$item->setCount($item->getCount() - 1);
						$player->getInventory()->setIteminhand($item);
						$this->Main->ContractMaid($player, $eid);
						$player->sendPopup("メイドさんとの契約が成立しました。");
					}elseif($itemid === $control and $maidplayername === $name){
						if($player->isSneaking()){
							$player->sendForm(new MaidSugarForm($this->Maid, $player, $eid));
						}else{
							$item->setCount($item->getCount() - 1);
							$player->getInventory()->setIteminhand($item);
							$this->Main->EatSugar($eid);
							if($this->Maid->Maiddata[$eid]["speed"] > 0){
								$this->Maid->Maiddata[$eid]["speed"] = 0;
								$this->Maid->Maiddata[$eid]["target"] = $player->getid();
								$player->sendPopup("待機モードにしました。");
							}else{
								$this->Maid->Maiddata[$eid]["speed"] = $this->Main->MaidSpeed;
								$player->sendPopup("待機モードを解除しました。");
							}
						}
					}elseif($itemid === $instruction and $maidplayername === $name){
						$item->setCount($item->getCount() - 1);
						$player->getInventory()->setIteminhand($item);
						$mode = $this->Maid->Maiddata[$eid]["mode"];
						if($mode === 0){
							$this->Maid->Maiddata[$eid]["mode"] = 1;
							$this->Maid->Maiddata[$eid]["target"] = $player->getid();
							$player->sendPopup("自由行動の指示を解除しました。");
						}else{
							$this->Maid->Maiddata[$eid]["mode"] = 0;
							$this->Maid->Maiddata[$eid]["target"] = 0;
							$this->Maid->Maiddata[$eid]["speed"] = $this->Main->MaidSpeed;
						$player->sendPopup("自由行動の指示をしました。");
						}
					}elseif($itemid === 421 and $maidplayername === $name){
						if($item->getCustomName() !== ""){
							$this->Maid->Maiddata[$eid]["name"] = $item->getCustomName();
							$item->setCount($item->getCount() - 1);
							$player->getInventory()->setIteminhand($item);
							foreach($this->Main->getServer()->getOnlinePlayers() as $players){
								$this->Maid->Redisplay($eid, $players);
							}
						}
					}elseif($itemid >= 298 and $itemid <= 317 and $maidplayername === $name){
						$head = $this->Maid->Maiddata[$eid]["helmet"];
						$chest = $this->Maid->Maiddata[$eid]["chestplate"];
						$legs = $this->Maid->Maiddata[$eid]["leggings"];
						$boots = $this->Maid->Maiddata[$eid]["boots"];
						if($itemid === 299 or $itemid === 303 or $itemid === 307 or $itemid === 311 or $itemid === 315){
							$item->setCount(1);
							$player->getInventory()->removeItem($item);
							$player->getInventory()->addItem($chest);
							$this->Main->MaidEquipArmor($eid, $item, 1);
						}elseif($itemid === 300 or $itemid === 304 or $itemid === 308 or $itemid === 312 or $itemid === 316){
							$item->setCount(1);
							$player->getInventory()->removeItem($item);
							$player->getInventory()->addItem($legs);
							$this->Main->MaidEquipArmor($eid, $item, 2);
						}elseif($itemid === 301 or $itemid === 305 or $itemid === 309 or $itemid === 313 or $itemid === 317){
							$item->setCount(1);
							$player->getInventory()->removeItem($item);
							$player->getInventory()->addItem($boots);
							$this->Main->MaidEquipArmor($eid, $item, 3);
						}
					}elseif($maidplayername === $name){
						if($player->isSneaking()){
							$player->sendForm(new MaidStatusForm($this->Maid, $player, $eid));
						}else{
							$item->setCount(1);
							$player->getInventory()->removeItem($item);
							$maiditem = $this->Maid->Maiddata[$eid]["iteminhand"];
							$player->getInventory()->addItem($maiditem);
							$this->Main->MaidEquip($eid, $item);
						}
					}else{
						$damage = $this->Main->getAtk($item);
						if($this->Maid->Maiddata[$eid]["hp"] > 0){
							$this->Main->MaidDamage($player, $eid, $damage);
						}
					}
				}
			}
		}
	}

	public function onLogin(PlayerLoginEvent $event){
		$player = $event->getPlayer();
		$name = $event->getPlayer()->getName();
		if(isset($this->Main->data[$name])){
		
		}else{
			$this->Main->data[$name] = [
			"name" => $name,
			"MaidCount" => 0,
			];
		}
	}

	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		foreach($this->Main->eid as $eid){
			$this->Main->Redisplay($eid, $player);
		}
	}

	public function onQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		$this->Main->PlayerMaidRemove($player);
	}

	public function onTouch(PlayerInteractEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();
		$block = $event->getBlock();
		$pitem = $player->getInventory()->getIteminhand();
		$call = $this->Main->config->get("Call");
		if($pitem->getId() === 383 and $pitem->getDamage() === 151){
			$pitem->setCount($pitem->getCount() - 1);
			$player->getInventory()->setIteminhand($pitem);
			$pos = new Vector3($block->x, $block->y + 1, $block->z);
			$level = $player->getLevel();
			$skin = $this->Main->getConfigSkin();
			$this->Main->MaidSpawn($pos, $level, $skin);
		}elseif($pitem->getId() === $call){
			$this->Main->PlayerMaidSpawn($player);
		}
	}

	public function onEntityDamage(EntityDamageEvent $event){
		if ($event instanceof EntityDamageByEntityEvent) {
			$player = $event->getDamager();
			$entity = $event->getEntity();
			if ($player instanceof Player){
				$name = $player->getName();
				foreach($this->Main->eid as $eid){
					$maidplayername = $this->Maid->Maiddata[$eid]["playername"];
					$maidspeed = $this->Maid->Maiddata[$eid]["speed"];
					if($maidplayername === $name and $maidspeed !== 0){
						$this->Maid->Maiddata[$eid]["target"] = $entity->getid();
					}
				}
			}
		}
	}
}