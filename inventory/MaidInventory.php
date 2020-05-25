<?php

namespace littleMaidMobPE\inventory;

use littleMaidMobPE\Maid;

use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\inventory\BaseInventory;
use pocketmine\inventory\ContainerInventory;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\WindowTypes;

class MaidInventory extends ContainerInventory{

	private $player;
	private $eid;
	private $maid;

	public function __construct(Player $player, int $eid, Maid $maid){
		$this->player = $player;
		$this->eid = $eid;
		$this->Maid = $maid;
		
		$pos = new Vector3($player->getFloorX(), $player->getFloorY() + 3, $player->getFloorZ());
		parent::__construct($pos);
		
		$this->setMaidItem();
	}

	public function getName(): string{
		return "MaidInventory";
	}

	public function getDefaultSize(): int{
		return 27;
	}

	public function getNetworkType(): int{
		return WindowTypes::CONTAINER;
	}

	public function onOpen(Player $who): void{
		BaseInventory::onOpen($who);
	}

	public function onClose(Player $who): void{
		BaseInventory::onClose($who);
		
		$holder = $this->getHolder();
		if($who->getWindowId($this) == -1){
			$who->getLevel()->sendBlocks([$who], [$holder, $holder->add(1, 0, 0)]);
			return;
		}
		
		$this->Maid->CloseInventory($this->player, $this->eid);
		if(!isset($this->Maid->Maiddata[$this->eid]))
			return;
		
		for($i = 0; $i <= 26; $i++){
			$item = $this->getItem($i);
			$this->Maid->Maiddata[$this->eid]["inventory"][$i]["id"] = $item->getid();
			$this->Maid->Maiddata[$this->eid]["inventory"][$i]["damage"] = $item->getDamage();
			$this->Maid->Maiddata[$this->eid]["inventory"][$i]["amount"] = $item->getCount();
		}
	}

	public function setItem(int $index, Item $item, bool $send = true): bool{
		$bool = parent::setItem($index, $item, $send);
		return $bool;
	}

	public function setMaidItem(): void{
		for($i = 0; $i <= 26; $i++){
			$id = $this->Maid->Maiddata[$this->eid]["inventory"][$i]["id"];
			$damage = $this->Maid->Maiddata[$this->eid]["inventory"][$i]["damage"];
			$amount = $this->Maid->Maiddata[$this->eid]["inventory"][$i]["amount"];
			$this->setItem($i, Item::get($id, $damage, $amount));
		}
	}
}