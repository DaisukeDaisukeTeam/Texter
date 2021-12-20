<?php

declare(strict_types=1);

namespace jp\mcbe\fuyutsuki\Texter\text;

use pocketmine\{
	world\World,
	player\Player
};

/**
 * Interface Send-able
 * @package jp\mcbe\fuyutsuki\Texter\text
 */
interface Sendable {

	public function sendToPlayer(Player $player, SendType $type);

	public function sendToPlayers(array $players, SendType $type);

	public function sendToLevel(World $level, SendType $type);

}