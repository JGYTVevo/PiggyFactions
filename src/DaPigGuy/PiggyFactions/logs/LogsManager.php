<?php

namespace DaPigGuy\PiggyFactions\logs;

use DaPigGuy\PiggyFactions\factions\Faction;
use DaPigGuy\PiggyFactions\language\LanguageManager;
use DaPigGuy\PiggyFactions\PiggyFactions;
use pocketmine\Player;

class LogsManager
{
    /**@var LogsManager */
    private static $instance;

    /** @var PiggyFactions */
    private $plugin;

    public function __construct(PiggyFactions $plugin)
    {
        self::$instance = $this;
        $this->plugin = $plugin;
        $plugin->getDatabase()->executeGeneric("piggyfactions.logs.init");
    }

    public static function getInstance(): LogsManager
    {
        return self::$instance;
    }

    public function sendAllLogs(Faction $faction, Player $player, int $offset = 0, int $count = 15, callable $onSelect = null): void
    {
        if ($onSelect === null) {
            $onSelect = function (array $rows) use ($player): void {
                $message = $this->parseDataToMessage($rows, LanguageManager::getInstance()->getPlayerLanguage($player));
                $player->sendMessage($message);
            };
        }
        $psVars = ["faction" => $faction->getId(), "count" => $count, "startpoint" => $offset];
        $this->plugin->getDatabase()->executeSelect("piggyfactions.logs.loadall", $psVars, $onSelect);
    }

    public function sendLogsByAction(Faction $faction, Player $player, string $action, int $offset = 0, int $count = 15, callable $onSelect = null): void
    {
        if ($onSelect === null) {
            $onSelect = function (array $rows) use ($player): void {
                if (count($rows) === 0) {
                    LanguageManager::getInstance()->sendMessage($player, "logs.invalid-log");
                    return;
                }
                $message = $this->parseDataToMessage($rows, LanguageManager::getInstance()->getPlayerLanguage($player));
                $player->sendMessage($message);
            };
        }
        $psVars = ["action" => $action, "faction" => $faction->getId(), "count" => $count, "startpoint" => $offset]; //someone pls help I cant name variables
        $this->plugin->getDatabase()->executeSelect("piggyfactions.logs.load", $psVars, $onSelect);
    }

    public function addFactionLog(Faction $faction, FactionLog $factionLog): void
    {
        $action = $factionLog->getName();
        $psVars = ["faction" => $faction->getId(), "action" => $action, "timestamp" => time(), "data" => json_encode($factionLog)];
        $this->plugin->getDatabase()->executeInsert("piggyfactions.logs.create", $psVars);
    }

    private function parseDataToMessage(array $rows, string $language = LanguageManager::DEFAULT_LANGUAGE): string
    {
        $message = "";
        foreach ($rows as $row) {
            $data = json_decode($row["data"], true);
            $message .= $this->parseMessageFromAction($row["action"], $data, $language) . "\n";
            $message = str_replace("{TIME}", date("Y-m-d H:i:s", $row["timestamp"]), $message);
        }
        return $message;
    }

    private function parseMessageFromAction(string $action, array $data, string $language)
    {
        switch ($action) {
            case FactionLog::KICK:
                return LanguageManager::getInstance()->getMessage($language, "logs.actions.kick", ["{KICKED}" => $data["kicked"], "{KICKER}" => $data["kicker"]]);
            case FactionLog::BAN:
                return LanguageManager::getInstance()->getMessage($language, "logs.actions.ban", ["{BANNED}" => $data["banned"], "{BANNEDBY}" => $data["bannedBy"]]);
            case FactionLog::UNBAN:
                return LanguageManager::getInstance()->getMessage($language, "logs.actions.unban", ["{UNBANNED}" => $data["unbanned"], "{UNBANNEDBY}" => $data["unbannedBy"]]);
            case FactionLog::INVITE:
                return LanguageManager::getInstance()->getMessage($language, "logs.actions.invite", ["{INVITED}" => $data["invited"], "{INVITEDBY}" => $data["invitedBy"]]);
            case FactionLog::LEADER_CHANGE:
                return LanguageManager::getInstance()->getMessage($language, "logs.actions.leader_change", ["{NEW}" => $data["new"], "{OLD}" => $data["old"]]);
            case FactionLog::JOIN:
                return LanguageManager::getInstance()->getMessage($language, "logs.actions.join", ["{JOINED}" => $data["joined"]]);
            case FactionLog::LEAVE:
                return LanguageManager::getInstance()->getMessage($language, "logs.actions.leave", ["{LEFT}" => $data["left"]]);
            default:
                return "";
        }
    }
}