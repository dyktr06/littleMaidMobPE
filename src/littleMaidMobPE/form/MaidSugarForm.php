<?php

namespace littleMaidMobPE\form;

use littleMaidMobPE\Maid;

use pocketmine\form\Form;
use pocketmine\Player;
use pocketmine\item\Item;

class MaidSugarForm implements Form{

	public function __construct(Maid $m, Player $player, int $eid) {
		$this->Maid = $m;
		$this->Player = $player;
		$this->eid = $eid;
	}

	public function handleResponse(Player $player, $data): void {
		if($data === null){
			return;
		}
		
		$eid = $this->eid;
		$player = $this->Player;
		$sugar_amount = $data[1];
		$control = $this->Maid->Main->config->get("Control");
		$item = Item::get($control, 0, $sugar_amount);
		$player->getInventory()->removeItem($item);
		$this->Maid->Maiddata[$eid]["sugar_amount"] += $sugar_amount;
		$player->sendPopup("メイドさんに ".$sugar_amount." 個の砂糖を渡しました。");
	}

	public function jsonSerialize(){
		$player = $this->Player;
		$item = $player->getInventory()->getItemInHand();
		$itemcount = $item->getCount();
		return [
			'type' => 'custom_form',
			'title' => '【 メイドさんへの給料 】',
			'content' => [
				[
					'type' => 'label',
					'text' => "§lメイドさんに渡す砂糖の数を選択してください。"
				],
				[
					'type' => 'slider',
					'text' => '砂糖の数',
					'min' => 0,
					'max' => $itemcount,
					'default' => 1
				]
			]
		];
	}
}