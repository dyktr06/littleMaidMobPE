<?php

namespace littleMaidMobPE;

use littleMaidMobPE\Maid;
use littleMaidMobPE\EventListener;
use littleMaidMobPE\task\RemoveMaid;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\entity\Entity;
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\Skin;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\UUID;

class Main extends PluginBase implements Listener{

	// API <-

	// メイドさんかを判定
	public function isMaid(int $eid): bool{
		if(isset($this->Maid->Maiddata[$eid])){
			return true;
		}else{
			return false;
		}
	}

	// 契約されているメイドさんかを判定
	public function isPlayerMaid(int $eid): bool{
		if(!$this->isMaid($eid))
			return false;
		
		if($this->Maid->Maiddata[$eid]["playername"] !== "" or $this->Maid->Maiddata[$eid]["playerid"] !== ""){
			return true;
		}else{
			return false;
		}
	}

	// Configに設定されているSkinを取得
	public function getConfigSkin(): Skin{
		$geometryJsonEncoded = base64_decode($this->config->get("Geometrydata"));
		if($geometryJsonEncoded !== "") $geometryJsonEncoded = \json_encode(\json_decode($geometryJsonEncoded));
		
		$skin = new Skin(base64_decode($this->config->get("Skinid")), base64_decode($this->config->get("Skindata")), base64_decode($this->config->get("Capedata")), base64_decode($this->config->get("Geometryname")), $geometryJsonEncoded);
		return $skin;
	}

	// メイドさんの持っているアイテムを取得
	public function getMaidItem(int $eid): ?Item{
		if($this->isMaid($eid)){
			return $this->Maid->Maiddata[$eid]["item"];
		}else{
			return null;
		}
	}

	/* 
	* メイドさんの着ている防具を取得する
	* $part 0 = ヘルメット, 1 = チェストプレート, 2 = レギンス, 3 = ブーツ
	*/
	public function getMaidArmor(int $eid, int $part): ?Item{
		if($this->isMaid($eid)){
			switch($part){
				case 0:
					return $this->Maid->Maiddata[$eid]["helmet"];
					break;
				case 1:
					return $this->Maid->Maiddata[$eid]["chestplate"];
					break;
				case 2:
					return $this->Maid->Maiddata[$eid]["leggings"];
					break;
				case 3:
					return $this->Maid->Maiddata[$eid]["boots"];
					break;
				default:
					return Item::get(0 ,0, 0);
			}
		}else{
			return null;
		}
	}

	// メイドさんの体力を取得
	public function getMaidHealth(int $eid): ?int{
		if($this->isMaid($eid)){
			return $this->Maid->Maiddata[$eid]["hp"];
		}else{
			return null;
		}
	}

	// メイドさんの体力を設定
	public function setMaidHealth(int $eid, int $value): void{
		if($this->isMaid($eid)){
			$this->Maid->Maiddata[$eid]["hp"] = $value;
		}
	}

	// メイドさんの最大体力を取得
	public function getMaidMaxHealth(int $eid): ?int{
		if($this->isMaid($eid)){
			return $this->Maid->Maiddata[$eid]["maxhp"];
		}else{
			return null;
		}
	}

	// メイドさんの最大体力を設定
	public function setMaidMaxHealth(int $eid, int $value): void{
		if($this->isMaid($eid)){
			$this->Maid->Maiddata[$eid]["maxhp"] = $value;
		}
	}

	// メイドさんの攻撃力を取得
	public function getMaidATK(int $eid): ?int{
		if($this->isMaid($eid)){
			return $this->Maid->Maiddata[$eid]["atk"];
		}else{
			return null;
		}
	}

	// メイドさんの攻撃力を設定
	public function setMaidATK(int $eid, int $value): void{
		if($this->isMaid($eid)){
			$this->Maid->Maiddata[$eid]["atk"] = $value;
		}
	}

	// メイドさんの防御力を取得
	public function getMaidDefence(int $eid): ?int{
		if($this->isMaid($eid)){
			return $this->Maid->Maiddata[$eid]["def"];
		}else{
			return null;
		}
	}

	// メイドさんのスピードを取得
	public function getMaidSpeed(int $eid): ?int{
		if($this->isMaid($eid)){
			return $this->Maid->Maiddata[$eid]["speed"];
		}else{
			return null;
		}
	}

	// メイドさんのスピードを設定
	public function setMaidSpeed(int $eid, int $value): void{
		if($this->isMaid($eid)){
			$this->Maid->Maiddata[$eid]["speed"] = $value;
		}
	}

	// メイドさんの攻撃範囲(リーチ)を設定
	public function setMaidATKRange(int $eid, int $value): void{
		if($this->isMaid($eid)){
			$this->Maid->Maiddata[$eid]["atkrange"] = $value;
			$this->Maid->Maiddata[$eid]["enemydistance"] = $value;
		}
	}

	// メイドさんの再攻撃時間を設定
	public function setMaidReATK(int $eid, int $value): void{
		if($this->isMaid($eid)){
			$this->Maid->Maiddata[$eid]["reatk"] = $value;
		}
	}

	// メイドさんのスキンを取得
	public function getMaidSkin(int $eid): ?Skin{
		if($this->isMaid($eid)){
			return $this->Maid->Maiddata[$eid]["skin"];
		}else{
			return null;
		}
	}

	// メイドさんの座標を取得
	public function getMaidPosition(int $eid): ?Vector3{
		if($this->isMaid($eid)){
			$pos = new Vector3($this->Maid->Maiddata[$eid]["x"], $this->Maid->Maiddata[$eid]["y"], $this->Maid->Maiddata[$eid]["z"]);
			return $pos;
		}else{
			return null;
		}
	}

	// メイドさんのワールドを取得
	public function getMaidLevel(int $eid): ?Level{
		if($this->isMaid($eid)){
			return $this->Maid->Maiddata[$eid]["level"];
		}else{
			return null;
		}
	}

	// メイドさんの持っている砂糖の数を取得
	public function getMaidSugarCount(int $eid): ?int{
		if($this->isMaid($eid)){
			return $this->Maid->Maiddata[$eid]["sugar_amount"];
		}else{
			return null;
		}
	}

	// メイドさんの持っている砂糖の数を設定
	public function setMaidSugarCount(int $eid, int $value): void{
		if($this->isMaid($eid)){
			$this->Maid->Maiddata[$eid]["sugar_amount"] = $value;
		}
	}

	// メイドさんの雇用期間を取得(秒)
	public function getPlayerMaidTime(int $eid): ?int{
		if($this->isPlayerMaid($eid)){
			return floor($this->Maid->Maiddata[$eid]["time"] / 20);
		}else{
			return 0;
		}
	}

	// メイドさんの雇用期間を設定(秒)
	public function setPlayerMaidTime(int $eid, int $value): void{
		if($this->isPlayerMaid($eid)){
			$this->Maid->Maiddata[$eid]["time"] = $value * 20;
		}
	}

	// メイドさんの契約しているプレイヤーの名前を取得
	public function getMaidPlayerName(int $eid): ?string{
		if($this->isMaid($eid)){
			return $this->Maid->Maiddata[$eid]["playername"];
		}else{
			return null;
		}
	}

	// プレイヤーの契約しているメイドさんの数を取得
	public function getMaidCount(Player $player): int{
		return count($this->getPlayerMaidEntityRuntimeId($player));
	}

	// プレイヤーの契約しているメイドさんのeidを取得
	public function getPlayerMaidEntityRuntimeId(Player $player): int{
		$eid = array();
		$name = $player->getName();
		$maidcount = $this->data[$name]["MaidCount"];
		for($i = 1; $i <= $maidcount; $i++){
			if(isset($this->data[$name][$i])){
				if($this->data[$name][$i]["spawn"] === 1){
					array_push($eid, $this->data[$name][$i]["eid"]);
				}
			}
		}
		return $eid;
	}

	// メイドさんにスキンをセットする
	public function setMaidSkin(int $eid, Skin $skin): void{
		if(!$this->isMaid($eid))
			return;
		
		$this->Maid->Maiddata[$eid]["skin"] = $skin;
		$skinid = base64_encode($skin->getSkinId());
		$skindata = base64_encode($skin->getSkinData());
		$capedata = base64_encode($skin->getCapeData());
		$geometryname = base64_encode($skin->getGeometryName());
		$geometrydata = base64_encode($skin->getGeometryData());
		$this->data[$name][$id]["skinid"] = $skinid;
		$this->data[$name][$id]["skindata"] = $skindata;
		$this->data[$name][$id]["capedata"] = $capedata;
		$this->data[$name][$id]["geometryname"] = $geometryname;
		$this->data[$name][$id]["geometrydata"] = $geometrydata;
		foreach($this->getServer()->getOnlinePlayers() as $players){
			$this->Redisplay($eid, $players);
		}
	}

	// メイドさんをスポーンさせる
	public function MaidSpawn(Vector3 $pos, Level $level, Skin $skin): void{
		$this->Maid->Spawn($pos, $level, $skin);
	}

	// プレイヤーの契約したメイドさんをスポーンさせる
	public function PlayerMaidSpawn(Player $player): void{
		$this->Maid->PlayerMaidSpawn($player);
	}

	// メイドさんがアイテムを装着する
	public function MaidEquip(int $eid, Item $item): void{
		$this->Maid->ItemEquip($eid, $item);
	}

	/* 
	* メイドさんが防具を装着する
	* $part 1 = チェストプレート, 2 = レギンス, 3 = ブーツ
	*/
	public function MaidEquipArmor(int $eid, Item $item, int $part): void{
		$this->Maid->ArmorEquip($eid, $item, $part);
	}

	// メイドさんがダメージを与える
	public function MaidATK(int $eid, Entity $target): void{
		$this->Maid->Attack($eid, $target);
	}

	//　メイドさんがダメージを食らう
	public function MaidDamage(Entity $entity, int $eid, int $damage): void{
		$this->Maid->Damaged($entity, $eid, $damage);
	}

	// メイドさんが死ぬ
	public function MaidDeath(int $eid): void{
		$this->Maid->Death($eid);
	}

 	// メイドさんと契約する
	public function ContractMaid(Player $player, int $eid): void{
		$this->Maid->Contract($player, $eid);
	}

	// メイドさんが砂糖を持っているかどうかを調べる
	public function MaidSugarCheck(int $eid): bool{
		if(!$this->isMaid($eid))
			return false;

		if($this->Maid->Maiddata[$eid]["sugar_amount"] >= 1){
			return true;
		}else{
			for($i = 0; $i <= 26; $i++){
				if($this->Maid->Maiddata[$eid]["inventory"][$i]["id"] === $this->config->get("Control") and $this->Maid->Maiddata[$eid]["inventory"][$i]["amount"] >= 1){
					return true;
				}
			}
			return false;
		}
	}

	// メイドさんが砂糖を食べる
	public function EatSugar(int $eid): void{
		$this->Maid->EatSugar($eid);
	}

	// メイドさんを野良の状態に戻す
	public function MaidReset(int $eid): void{
		$this->Maid->Reset($eid);
	}

	// メイドさんを動かす
	public function MaidMove(int $eid, Vector3 $pos, float $yaw, float $pitch): void{
		$this->Maid->Move($eid, $pos, $yaw, $pitch);
	}

	// メイドさんがアイテムを拾う
	public function MaidPickupItem(int $eid, ItemEntity $target): void{
		$this->Maid->PickupItem($eid, $target);
	}

	// メイドさんの再表示
	public function Redisplay(int $eid, Player $player): void{
		$this->Maid->Redisplay($eid, $player);
	}

	// API ->

	public function onEnable(){
		$this->eid = [];
		$this->MaidSpeed = 2;
		$this->MaidSize = 0.65;
		
		$this->Maid = new Maid($this);
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this, $this->Maid), $this);
		
		if(!file_exists($this->getDataFolder())){
			mkdir($this->getDataFolder(), 0744, true);
		}
		
		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
			"SpawneggID" => 383,
			"SpawneggDAMAGE" => 151,
			"Call" => 369,
			"Contract" => 354,
			"Control" => 353,
			"Instruction" => 288,
			"Skinid" => "YzE4ZTY1YWEtN2IyMS00NjM3LTliNjMtOGFkNjM2MjJlZjAxX0N1c3RvbVNsaW0=",
			"Skindata" => "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",
			"Capedata" => "",
			"Geometryname" => "Z2VvbWV0cnkuaHVtYW5vaWQuY3VzdG9tU2xpbQ==",
			"Geometrydata" => "eyJnZW9tZXRyeS5odW1hbm9pZCI6eyJib25lcyI6W3sibmFtZSI6ImJvZHkiLCJwaXZvdCI6WzAsMjQsMF0sImN1YmVzIjpbeyJvcmlnaW4iOlstNCwxMiwtMl0sInNpemUiOls4LDEyLDRdLCJ1diI6WzE2LDE2XX1dfSx7Im5hbWUiOiJ3YWlzdCIsIm5ldmVyUmVuZGVyIjp0cnVlLCJwaXZvdCI6WzAsMTIsMF19LHsibmFtZSI6ImhlYWQiLCJwaXZvdCI6WzAsMjQsMF0sImN1YmVzIjpbeyJvcmlnaW4iOlstNCwyNCwtNF0sInNpemUiOls4LDgsOF0sInV2IjpbMCwwXX1dfSx7Im5hbWUiOiJoYXQiLCJwaXZvdCI6WzAsMjQsMF0sImN1YmVzIjpbeyJvcmlnaW4iOlstNCwyNCwtNF0sInNpemUiOls4LDgsOF0sInV2IjpbMzIsMF0sImluZmxhdGUiOjAuNX1dLCJuZXZlclJlbmRlciI6dHJ1ZX0seyJuYW1lIjoicmlnaHRBcm0iLCJwaXZvdCI6Wy01LDIyLDBdLCJjdWJlcyI6W3sib3JpZ2luIjpbLTgsMTIsLTJdLCJzaXplIjpbNCwxMiw0XSwidXYiOls0MCwxNl19XX0seyJuYW1lIjoibGVmdEFybSIsInBpdm90IjpbNSwyMiwwXSwiY3ViZXMiOlt7Im9yaWdpbiI6WzQsMTIsLTJdLCJzaXplIjpbNCwxMiw0XSwidXYiOls0MCwxNl19XSwibWlycm9yIjp0cnVlfSx7Im5hbWUiOiJyaWdodExlZyIsInBpdm90IjpbLTEuOSwxMiwwXSwiY3ViZXMiOlt7Im9yaWdpbiI6Wy0zLjksMCwtMl0sInNpemUiOls0LDEyLDRdLCJ1diI6WzAsMTZdfV19LHsibmFtZSI6ImxlZnRMZWciLCJwaXZvdCI6WzEuOSwxMiwwXSwiY3ViZXMiOlt7Im9yaWdpbiI6Wy0wLjEsMCwtMl0sInNpemUiOls0LDEyLDRdLCJ1diI6WzAsMTZdfV0sIm1pcnJvciI6dHJ1ZX1dfSwiZ2VvbWV0cnkuY2FwZSI6eyJ0ZXh0dXJld2lkdGgiOjY0LCJ0ZXh0dXJlaGVpZ2h0IjozMiwiYm9uZXMiOlt7Im5hbWUiOiJjYXBlIiwicGl2b3QiOlswLDI0LC0zXSwiY3ViZXMiOlt7Im9yaWdpbiI6Wy01LDgsLTNdLCJzaXplIjpbMTAsMTYsMV0sInV2IjpbMCwwXX1dLCJtYXRlcmlhbCI6ImFscGhhIn1dfSwiZ2VvbWV0cnkuaHVtYW5vaWQuY3VzdG9tOmdlb21ldHJ5Lmh1bWFub2lkIjp7ImJvbmVzIjpbeyJuYW1lIjoiaGF0IiwibmV2ZXJSZW5kZXIiOmZhbHNlLCJtYXRlcmlhbCI6ImFscGhhIiwicGl2b3QiOlswLDI0LDBdfSx7Im5hbWUiOiJsZWZ0QXJtIiwicmVzZXQiOnRydWUsIm1pcnJvciI6ZmFsc2UsInBpdm90IjpbNSwyMiwwXSwiY3ViZXMiOlt7Im9yaWdpbiI6WzQsMTIsLTJdLCJzaXplIjpbNCwxMiw0XSwidXYiOlszMiw0OF19XX0seyJuYW1lIjoicmlnaHRBcm0iLCJyZXNldCI6dHJ1ZSwicGl2b3QiOlstNSwyMiwwXSwiY3ViZXMiOlt7Im9yaWdpbiI6Wy04LDEyLC0yXSwic2l6ZSI6WzQsMTIsNF0sInV2IjpbNDAsMTZdfV19LHsibmFtZSI6InJpZ2h0SXRlbSIsInBpdm90IjpbLTYsMTUsMV0sIm5ldmVyUmVuZGVyIjp0cnVlLCJwYXJlbnQiOiJyaWdodEFybSJ9LHsibmFtZSI6ImxlZnRTbGVldmUiLCJwaXZvdCI6WzUsMjIsMF0sImN1YmVzIjpbeyJvcmlnaW4iOls0LDEyLC0yXSwic2l6ZSI6WzQsMTIsNF0sInV2IjpbNDgsNDhdLCJpbmZsYXRlIjowLjI1fV0sIm1hdGVyaWFsIjoiYWxwaGEifSx7Im5hbWUiOiJyaWdodFNsZWV2ZSIsInBpdm90IjpbLTUsMjIsMF0sImN1YmVzIjpbeyJvcmlnaW4iOlstOCwxMiwtMl0sInNpemUiOls0LDEyLDRdLCJ1diI6WzQwLDMyXSwiaW5mbGF0ZSI6MC4yNX1dLCJtYXRlcmlhbCI6ImFscGhhIn0seyJuYW1lIjoibGVmdExlZyIsInJlc2V0Ijp0cnVlLCJtaXJyb3IiOmZhbHNlLCJwaXZvdCI6WzEuOSwxMiwwXSwiY3ViZXMiOlt7Im9yaWdpbiI6Wy0wLjEsMCwtMl0sInNpemUiOls0LDEyLDRdLCJ1diI6WzE2LDQ4XX1dfSx7Im5hbWUiOiJsZWZ0UGFudHMiLCJwaXZvdCI6WzEuOSwxMiwwXSwiY3ViZXMiOlt7Im9yaWdpbiI6Wy0wLjEsMCwtMl0sInNpemUiOls0LDEyLDRdLCJ1diI6WzAsNDhdLCJpbmZsYXRlIjowLjI1fV0sInBvcyI6WzEuOSwxMiwwXSwibWF0ZXJpYWwiOiJhbHBoYSJ9LHsibmFtZSI6InJpZ2h0UGFudHMiLCJwaXZvdCI6Wy0xLjksMTIsMF0sImN1YmVzIjpbeyJvcmlnaW4iOlstMy45LDAsLTJdLCJzaXplIjpbNCwxMiw0XSwidXYiOlswLDMyXSwiaW5mbGF0ZSI6MC4yNX1dLCJwb3MiOlstMS45LDEyLDBdLCJtYXRlcmlhbCI6ImFscGhhIn0seyJuYW1lIjoiamFja2V0IiwicGl2b3QiOlswLDI0LDBdLCJjdWJlcyI6W3sib3JpZ2luIjpbLTQsMTIsLTJdLCJzaXplIjpbOCwxMiw0XSwidXYiOlsxNiwzMl0sImluZmxhdGUiOjAuMjV9XSwibWF0ZXJpYWwiOiJhbHBoYSJ9XX0sImdlb21ldHJ5Lmh1bWFub2lkLmN1c3RvbVNsaW06Z2VvbWV0cnkuaHVtYW5vaWQiOnsiYm9uZXMiOlt7Im5hbWUiOiJoYXQiLCJuZXZlclJlbmRlciI6ZmFsc2UsIm1hdGVyaWFsIjoiYWxwaGEifSx7Im5hbWUiOiJsZWZ0QXJtIiwicmVzZXQiOnRydWUsIm1pcnJvciI6ZmFsc2UsInBpdm90IjpbNSwyMS41LDBdLCJjdWJlcyI6W3sib3JpZ2luIjpbNCwxMS41LC0yXSwic2l6ZSI6WzMsMTIsNF0sInV2IjpbMzIsNDhdfV19LHsibmFtZSI6InJpZ2h0QXJtIiwicmVzZXQiOnRydWUsInBpdm90IjpbLTUsMjEuNSwwXSwiY3ViZXMiOlt7Im9yaWdpbiI6Wy03LDExLjUsLTJdLCJzaXplIjpbMywxMiw0XSwidXYiOls0MCwxNl19XX0seyJwaXZvdCI6Wy02LDE0LjUsMV0sIm5ldmVyUmVuZGVyIjp0cnVlLCJuYW1lIjoicmlnaHRJdGVtIiwicGFyZW50IjoicmlnaHRBcm0ifSx7Im5hbWUiOiJsZWZ0U2xlZXZlIiwicGl2b3QiOls1LDIxLjUsMF0sImN1YmVzIjpbeyJvcmlnaW4iOls0LDExLjUsLTJdLCJzaXplIjpbMywxMiw0XSwidXYiOls0OCw0OF0sImluZmxhdGUiOjAuMjV9XSwibWF0ZXJpYWwiOiJhbHBoYSJ9LHsibmFtZSI6InJpZ2h0U2xlZXZlIiwicGl2b3QiOlstNSwyMS41LDBdLCJjdWJlcyI6W3sib3JpZ2luIjpbLTcsMTEuNSwtMl0sInNpemUiOlszLDEyLDRdLCJ1diI6WzQwLDMyXSwiaW5mbGF0ZSI6MC4yNX1dLCJtYXRlcmlhbCI6ImFscGhhIn0seyJuYW1lIjoibGVmdExlZyIsInJlc2V0Ijp0cnVlLCJtaXJyb3IiOmZhbHNlLCJwaXZvdCI6WzEuOSwxMiwwXSwiY3ViZXMiOlt7Im9yaWdpbiI6Wy0wLjEsMCwtMl0sInNpemUiOls0LDEyLDRdLCJ1diI6WzE2LDQ4XX1dfSx7Im5hbWUiOiJsZWZ0UGFudHMiLCJwaXZvdCI6WzEuOSwxMiwwXSwiY3ViZXMiOlt7Im9yaWdpbiI6Wy0wLjEsMCwtMl0sInNpemUiOls0LDEyLDRdLCJ1diI6WzAsNDhdLCJpbmZsYXRlIjowLjI1fV0sIm1hdGVyaWFsIjoiYWxwaGEifSx7Im5hbWUiOiJyaWdodFBhbnRzIiwicGl2b3QiOlstMS45LDEyLDBdLCJjdWJlcyI6W3sib3JpZ2luIjpbLTMuOSwwLC0yXSwic2l6ZSI6WzQsMTIsNF0sInV2IjpbMCwzMl0sImluZmxhdGUiOjAuMjV9XSwibWF0ZXJpYWwiOiJhbHBoYSJ9LHsibmFtZSI6ImphY2tldCIsInBpdm90IjpbMCwyNCwwXSwiY3ViZXMiOlt7Im9yaWdpbiI6Wy00LDEyLC0yXSwic2l6ZSI6WzgsMTIsNF0sInV2IjpbMTYsMzJdLCJpbmZsYXRlIjowLjI1fV0sIm1hdGVyaWFsIjoiYWxwaGEifV19fQ=="
		]);
		$this->config->save();
		
		$this->playerdata = new Config($this->getDataFolder() . "data.yml", Config::YAML, []);
		$this->data = $this->playerdata->getAll();
		
		$id = $this->config->get("SpawneggID");
		$damage = $this->config->get("SpawneggDAMAGE");
		$spawnegg = Item::get($id, $damage, 1)->setCustomName("リトルメイドを出現させる");
		Item::addCreativeItem($spawnegg);
		
		$this->getServer()->getLogger()->info("§dlittleMaidMobPEを読み込みました！");
	}

	public function onDisable(){
		foreach($this->getServer()->getOnlinePlayers() as $players){
			$this->PlayerMaidRemove($players);
		}
		
		foreach($this->data as $t){
			$this->playerdata->set($t["name"], $t);
		}
		$this->playerdata->save();
	}

	public function PlayerMaidRemove(Player $player): void{
		$name = $player->getName();
		$maidcount = $this->data[$name]["MaidCount"];
		for($i = 1; $i <= $maidcount; $i++){
			if(isset($this->data[$name][$i])){
				if($this->data[$name][$i]["spawn"] === 1){
					$this->PlayerMaidStatusSave($player, $i);
					$this->data[$name][$i]["spawn"] = 0;
					$eid = $this->data[$name][$i]["eid"];
					$this->getScheduler()->scheduleDelayedTask(new RemoveMaid($this, $eid), 10);
				}
			}
		}
	}

	public function PlayerMaidStatusSave(Player $player, int $id): void{
		$name = $player->getName();
		if(!isset($this->data[$name][$id]))
			return;
		
		$eid = $this->data[$name][$id]["eid"];
		if(!$this->isMaid($eid))
			return;
		
		$this->data[$name][$id]["name"] = $this->Maid->Maiddata[$eid]["name"];
		$this->data[$name][$id]["iteminhand"] = $this->Maid->Maiddata[$eid]["iteminhand"]->getid();
		$this->data[$name][$id]["iteminhand_damage"] = $this->Maid->Maiddata[$eid]["iteminhand"]->getDamage();
		$this->data[$name][$id]["helmet"] = $this->Maid->Maiddata[$eid]["helmet"]->getid();
		$this->data[$name][$id]["helmet_damage"] = $this->Maid->Maiddata[$eid]["helmet"]->getDamage();
		$this->data[$name][$id]["chestplate"] = $this->Maid->Maiddata[$eid]["chestplate"]->getid();
		$this->data[$name][$id]["chestplate_damage"] = $this->Maid->Maiddata[$eid]["chestplate"]->getDamage();
		$this->data[$name][$id]["leggings"] = $this->Maid->Maiddata[$eid]["leggings"]->getid();
		$this->data[$name][$id]["leggings_damage"] = $this->Maid->Maiddata[$eid]["leggings"]->getDamage();
		$this->data[$name][$id]["boots"] = $this->Maid->Maiddata[$eid]["boots"]->getid();
		$this->data[$name][$id]["boots_damage"] = $this->Maid->Maiddata[$eid]["boots"]->getDamage();
		$this->data[$name][$id]["sugar_amount"] = $this->Maid->Maiddata[$eid]["sugar_amount"];
		$this->data[$name][$id]["maxhp"] = $this->Maid->Maiddata[$eid]["maxhp"];
		$this->data[$name][$id]["hp"] = $this->Maid->Maiddata[$eid]["hp"];
		$this->data[$name][$id]["time"] = $this->Maid->Maiddata[$eid]["time"];
		
		for($i = 0; $i <= 26; $i++){
			$this->data[$name][$id]["inventory"][$i]["id"] = $this->Maid->Maiddata[$eid]["inventory"][$i]["id"];
			$this->data[$name][$id]["inventory"][$i]["damage"] = $this->Maid->Maiddata[$eid]["inventory"][$i]["damage"];
			$this->data[$name][$id]["inventory"][$i]["amount"] = $this->Maid->Maiddata[$eid]["inventory"][$i]["amount"];
		}
	}

	public function isNearMaid(int $eid, Vector3 $pos): bool{
		foreach($this->eid as $maideid){
			$maidpos = $this->getMaidPosition($maideid);
			if($pos->distanceSquared($maidpos) <= 0.3 and $eid !== $maideid){
				return true;
			}
		}
		return false;
	}

	public function getAtk(Item $item): int{
		$damageTable = [
			Item::WOODEN_SWORD => 4,
			Item::GOLD_SWORD => 4,
			Item::STONE_SWORD => 5,
			Item::IRON_SWORD => 6,
			Item::DIAMOND_SWORD => 7,
			
			Item::WOODEN_AXE => 3,
			Item::GOLD_AXE => 3,
			Item::STONE_AXE => 3,
			Item::IRON_AXE => 5,
			Item::DIAMOND_AXE => 6,
			
			Item::WOODEN_PICKAXE => 2,
			Item::GOLD_PICKAXE => 2,
			Item::STONE_PICKAXE => 3,
			Item::IRON_PICKAXE => 4,
			Item::DIAMOND_PICKAXE => 5,
			
			Item::WOODEN_SHOVEL => 1,
			Item::GOLD_SHOVEL => 1,
			Item::STONE_SHOVEL => 2,
			Item::IRON_SHOVEL => 3,
			Item::DIAMOND_SHOVEL => 4,

			Item::BOW => 4,
		];
		
		return $damageTable[$item->getid()] ?? 1;
	}

	public function CalcYbyBlock(float $x, float $y, float $z, Level $level): float{
		$blockid = $level->getBlock(new Vector3($x, $y, $z))->getID();
		$blockid2 = $level->getBlock(new Vector3($x, $y - 1, $z))->getID();
		$blockid3 = $level->getBlock(new Vector3($x, $y + 1, $z))->getID();
		if($blockid !== 0 and $blockid !== 6 and $blockid !== 8 and $blockid !== 9 and $blockid !== 10 and $blockid !== 11 and $blockid !== 27 and $blockid !== 28 and $blockid !== 30 and $blockid !== 31 and $blockid !== 32 and $blockid !== 37 and $blockid !== 38 and $blockid !== 39 and $blockid !== 40 and $blockid !== 50 and $blockid !== 51 and $blockid !== 55 and $blockid !== 59 and $blockid !== 63 and $blockid !== 68 and $blockid !== 70 and $blockid !== 72 and $blockid !== 75 and $blockid !== 76 and $blockid !== 78 and $blockid !== 83 and $blockid !== 90 and $blockid !== 104 and $blockid !== 105 and $blockid !== 106 and $blockid !== 115 and $blockid !== 119 and $blockid !== 126 and $blockid !== 132 and $blockid !== 141 and $blockid !== 142 and $blockid !== 147 and $blockid !== 148 and $blockid !== 171 and $blockid !== 175  and $blockid !== 199 and $blockid !== 244){
			$y++;
		}elseif($blockid2 === 0 or $blockid2 === 6 or $blockid2 === 8 or $blockid2 === 9 or $blockid2 === 10 or $blockid2 === 11 or $blockid2 === 27 or $blockid2 === 28 or $blockid2 === 30 or $blockid2 === 31 or $blockid2 === 32 or $blockid2 === 37 or $blockid2 === 38 or $blockid2 === 39 or $blockid2 === 40 or $blockid2 === 50 or $blockid2 === 51 or $blockid2 === 55 or $blockid2 === 59 or $blockid2 === 63 or $blockid2 === 68 or $blockid2 === 70 or $blockid2 === 72 or $blockid2 === 75 or $blockid2 === 76 or $blockid2 === 78 or $blockid2 === 83 or $blockid2 === 90 or $blockid2 === 104 or $blockid2 === 105 or $blockid2 === 106 or $blockid2 === 115 or $blockid2 === 119 or $blockid2 === 126 or $blockid2 === 132 or $blockid2 === 141 or $blockid2 === 142 or $blockid2 === 147 or $blockid2 === 148 or $blockid2 === 171 or $blockid2 === 175 or $blockid2 === 199 or $blockid2 === 244){
			$y--;
		}elseif($blockid3 !== 0 and $blockid3 !== 8 and $blockid3 !== 9 and $blockid3 !== 10 and $blockid3 !== 11){
			$y++;
		}
		return $y;
	}

	public function SearchItemEntity($eid): bool{
		if(!$this->isMaid($eid))
			return false;
		
		if($this->Maid->Maiddata[$eid]["iteminhand"]->getId() === 261)
			return false;
		
		$searchdis = $this->Maid->Maiddata[$eid]["searchdistance"];
		$level = $this->Maid->Maiddata[$eid]["level"];
		$pos = $this->getMaidPosition($eid);
		foreach($level->getEntities() as $entities){
			if($pos->distance($entities) <= $searchdis and $entities instanceof ItemEntity){
				$this->Maid->Maiddata[$eid]["target"] = $entities->getid();
				return true;
			}
		}
		return false;
	}

	public function ReduceSugarInInventory(int $eid, int $amount = 1): void{
		if(!$this->isMaid($eid))
			return;

		for($i = 0; $i <= 26; $i++){
			if($this->Maid->Maiddata[$eid]["inventory"][$i]["id"] === $this->config->get("Control") and $this->Maid->Maiddata[$eid]["inventory"][$i]["amount"] >= $amount){
				$this->Maid->Maiddata[$eid]["inventory"][$i]["amount"] -= $amount;
				break;
			}
		}
	}
}