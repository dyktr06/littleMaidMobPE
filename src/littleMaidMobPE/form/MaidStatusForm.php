<?php

namespace littleMaidMobPE\form;

use littleMaidMobPE\Maid;

use pocketmine\form\Form;
use pocketmine\Player;

class MaidStatusForm implements Form{

	public function __construct(Maid $m, Player $player, int $eid) {
		$this->Maid = $m;
		$this->Player = $player;
		$this->eid = $eid;
	}

	public function handleResponse(Player $player, $data): void {
		if($data === null){
			return;
		}
	}

	public function jsonSerialize(){
		$eid = $this->eid;
		$hp = $this->Maid->Maiddata[$eid]["hp"];
		$maxhp = $this->Maid->Maiddata[$eid]["maxhp"];
		$atk = $this->Maid->Maiddata[$eid]["atk"];
		$def = $this->Maid->Maiddata[$eid]["def"];
		$time = $this->Maid->Maiddata[$eid]["time"];
		$minute = floor($time / 20 / 60);
		$second = floor($time / 20) - $minute * 60;
		$mode = ($this->Maid->Maiddata[$eid]["mode"] === 1) ? "OFF" : "ON";
		$sugar_amount = $this->Maid->Maiddata[$eid]["sugar_amount"];
		return [
			'type' => 'custom_form',
			'title' => '【 メイドさんのステータス 】',
			'content' => [
	                	[
					'type' => 'label',
					'text' => " §l§a体力 §f: ".$hp." / ".$maxhp." \n\n §c攻撃力 §f: ".$atk." \n\n §b防御力 §f: ".$def." \n\n §e雇用期間 §f: ".$minute." 分 ".$second." 秒 \n\n §d自由行動 §f: ".$mode." \n\n §f砂糖 §f: ".$sugar_amount." 個 \n\n "
				]
			]
		];
	}
}