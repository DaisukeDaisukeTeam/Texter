<?php

/**
 * // English
 *
 * Texter, the display FloatingTextPerticle plugin for PocketMine-MP
 * Copyright (c) 2019-2021 yuko fuyutsuki < https://github.com/fuyutsuki >
 *
 * This software is distributed under "NCSA license".
 * You should have received a copy of the NCSA license
 * along with this program.  If not, see
 * < https://opensource.org/licenses/NCSA >.
 *
 * ---------------------------------------------------------------------
 * // 日本語
 *
 * TexterはPocketMine-MP向けのFloatingTextPerticleを表示するプラグインです
 * Copyright (c) 2019-2021 yuko fuyutsuki < https://github.com/fuyutsuki >
 *
 * このソフトウェアは"NCSAライセンス"下で配布されています。
 * あなたはこのプログラムと共にNCSAライセンスのコピーを受け取ったはずです。
 * 受け取っていない場合、下記のURLからご覧ください。
 * < https://opensource.org/licenses/NCSA >
 */

declare(strict_types=1);

namespace jp\mcbe\fuyutsuki\Texter;

use jp\mcbe\fuyutsuki\Texter\command\TexterCommand;
use jp\mcbe\fuyutsuki\Texter\data\FloatingTextData;
use jp\mcbe\fuyutsuki\Texter\i18n\TexterLang;
use jp\mcbe\fuyutsuki\Texter\task\SendTextsTask;
use jp\mcbe\fuyutsuki\Texter\text\SendType;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

/**
 * Class EventListener
 * @package jp\mcbe\fuyutsuki\Texter
 */
class EventListener implements Listener {

	/** @var Plugin */
	private $plugin;

	public function __construct(Plugin $plugin) {
		$this->plugin = $plugin;
	}

	public function onJoinPlayer(PlayerJoinEvent $ev) {
		$player = $ev->getPlayer();
		$world = $player->getWorld();
		$sendTask = new SendTextsTask($this->plugin, $player, $world, new SendType(SendType::ADD));
		$handler = $this->plugin->getScheduler()->scheduleDelayedRepeatingTask($sendTask, SendTextsTask::DELAY_TICKS, SendTextsTask::TICKING_PERIOD);
		$sendTask->setHandler($handler);
	}

	public function onLoadLevel(WorldLoadEvent $ev) {
		$folderName = $ev->getWorld()->getFolderName();
		if (FloatingTextData::getInstance($folderName) === null) {
			$floatingTextData = new FloatingTextData($this->plugin, $folderName);
			$floatingTextData->generateFloatingTexts($this->plugin);
			$this->plugin->getLogger()->debug("Loaded FloatingTextCluster file: {$folderName}.json");
		}
	}

	public function onEntityLevelChange(EntityTeleportEvent $ev) {
		$entity = $ev->getEntity();
		if ($entity instanceof Player) {
			$from = $ev->getFrom();
			$to = $ev->getTo();
			$removeTask = new SendTextsTask($this->plugin, $entity, $from->getWorld(), new SendType(SendType::REMOVE));
			$addTask = new SendTextsTask($this->plugin, $entity, $to->getWorld(), new SendType(SendType::ADD));
			$scheduler = $this->plugin->getScheduler();
			$removeTaskHandler = $scheduler->scheduleDelayedRepeatingTask($removeTask, SendTextsTask::DELAY_TICKS, SendTextsTask::TICKING_PERIOD);
			$addTaskHandler = $scheduler->scheduleDelayedRepeatingTask($addTask, SendTextsTask::DELAY_TICKS, SendTextsTask::TICKING_PERIOD);
			$removeTask->setHandler($removeTaskHandler);
			$addTask->setHandler($addTaskHandler);
		}
	}

	public function onSendPacket(DataPacketSendEvent $ev) {

		foreach($ev->getPackets() as $pk){
			if ($pk->pid() === ProtocolInfo::AVAILABLE_COMMANDS_PACKET) {
				/** @var AvailableCommandsPacket $pk */
				if (isset($pk->commandData[TexterCommand::NAME])) {
					$locale = $ev->getTargets()[0]->getPlayerInfo()->getLocale();
					$texterCommand = $pk->commandData[TexterCommand::NAME];
					$texterCommand->description = TexterLang::fromLocale($locale)->translateString(TexterCommand::DESCRIPTION);
				}
			}
		}
	}
}