<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyFactions\commands;

use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\BaseSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\admin\AdminSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\admin\SetPowerSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\ChatSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\claims\ClaimSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\claims\MapSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\claims\SeeChunkSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\claims\UnclaimSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\flags\FlagSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\HelpSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\homes\HomeSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\homes\SetHomeSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\InfoSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\JoinSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\LeaveSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\management\BanSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\management\CreateSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\management\DescriptionSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\management\DisbandSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\management\InviteSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\management\KickSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\management\MotdSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\management\NameSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\management\UnbanSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\relations\AllySubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\relations\EnemySubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\relations\NeutralSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\relations\TruceSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\relations\UnallySubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\roles\DemoteSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\roles\LeaderSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\roles\PermissionSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\roles\PromoteSubCommand;
use DaPigGuy\PiggyFactions\commands\subcommands\TopSubCommand;
use DaPigGuy\PiggyFactions\language\LanguageManager;
use DaPigGuy\PiggyFactions\PiggyFactions;
use DaPigGuy\PiggyFactions\utils\ChatTypes;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class FactionCommand extends BaseCommand
{
    /** @var PiggyFactions */
    private $plugin;

    public function __construct(PiggyFactions $plugin, string $name, string $description = "", array $aliases = [])
    {
        $this->plugin = $plugin;
        parent::__construct($name, $description, $aliases);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($this->plugin->areFormsEnabled() && $sender instanceof Player) {
            $subcommands = array_filter($this->getSubCommands(), function (BaseSubCommand $subCommand, string $alias) use ($sender): bool {
                return $subCommand->getName() === $alias && $sender->hasPermission($subCommand->getPermission());
            }, ARRAY_FILTER_USE_BOTH);
            $form = new SimpleForm(function (Player $player, ?int $data) use ($subcommands): void {
                if ($data !== null) {
                    $subcommand = $subcommands[array_keys($subcommands)[$data]];
                    $subcommand->onRun($player, $subcommand->getName(), []);
                }
            });
            $form->setTitle(LanguageManager::getInstance()->getMessage(LanguageManager::getInstance()->getPlayerLanguage($sender), "forms.title"));
            foreach ($subcommands as $key => $subcommand) {
                $form->addButton(ucfirst($subcommand->getName()));
            }
            $sender->sendForm($form);
            return;
        }
        $this->sendUsage();
    }

    protected function prepare(): void
    {
        $this->setPermission("piggyfactions.command.faction.use");
        $this->registerSubCommand(new AdminSubCommand($this->plugin, "admin", "Toggle admin mode"));
        $this->registerSubCommand(new AllySubCommand($this->plugin, "ally", "Ally with other factions"));
        $this->registerSubCommand(new BanSubCommand($this->plugin, "ban", "Ban a member from your faction"));
        $this->registerSubCommand(new ChatSubCommand($this->plugin, ChatTypes::ALLY, "allychat", "Toggle ally chat", ["ac"]));
        $this->registerSubCommand(new ChatSubCommand($this->plugin, ChatTypes::FACTION, "chat", "Toggle faction chat", ["c"]));
        $this->registerSubCommand(new ClaimSubCommand($this->plugin, "claim", "Claim a chunk"));
        $this->registerSubCommand(new CreateSubCommand($this->plugin, "create", "Create a faction"));
        $this->registerSubCommand(new DescriptionSubCommand($this->plugin, "description", "Set faction description", ["desc"]));
        $this->registerSubCommand(new DemoteSubCommand($this->plugin, "demote", "Demote a faction member"));
        $this->registerSubCommand(new DisbandSubCommand($this->plugin, "disband", "Disband your faction"));
        $this->registerSubCommand(new EnemySubCommand($this->plugin, "enemy", "Mark faction as an enemy"));
        $this->registerSubCommand(new FlagSubCommand($this->plugin, "flag", "Manage faction flags"));
        $this->registerSubCommand(new HelpSubCommand($this->plugin, $this, "help", "Display command information"));
        $this->registerSubCommand(new HomeSubCommand($this->plugin, "home", "Teleport to faction home"));
        $this->registerSubCommand(new InfoSubCommand($this->plugin, "info", "Display faction info", ["who"]));
        $this->registerSubCommand(new InviteSubCommand($this->plugin, "invite", "Invite a player to your faction"));
        $this->registerSubCommand(new JoinSubCommand($this->plugin, "join", "Join a faction"));
        $this->registerSubCommand(new KickSubCommand($this->plugin, "kick", "Kick a member from your faction"));
        $this->registerSubCommand(new LeaderSubCommand($this->plugin, "leader", "Transfer leadership of your faction"));
        $this->registerSubCommand(new LeaveSubCommand($this->plugin, "leave", "Leave your faction"));
        $this->registerSubCommand(new MapSubCommand($this->plugin, "map", "View map of area"));
        $this->registerSubCommand(new MotdSubCommand($this->plugin, "motd", "Set faction MOTD"));
        $this->registerSubCommand(new NameSubCommand($this->plugin, "name", "Rename your faction"));
        $this->registerSubCommand(new NeutralSubCommand($this->plugin, "neutral", "Reset relation with another faction"));
        $this->registerSubCommand(new PermissionSubCommand($this->plugin, "permission", "Set faction role permissions", ["perms"]));
        $this->registerSubCommand(new PromoteSubCommand($this->plugin, "promote", "Promote a faction member"));
        $this->registerSubCommand(new SeeChunkSubCommand($this->plugin, "seechunk", "Toggle chunk visualizer", ["sc"]));
        $this->registerSubCommand(new SetHomeSubCommand($this->plugin, "sethome", "Set faction home"));
        $this->registerSubCommand(new SetPowerSubCommand($this->plugin, "setpower", "Set player power"));
        $this->registerSubCommand(new TopSubCommand($this->plugin, "top", "Display top factions", ["list"]));
        $this->registerSubCommand(new TruceSubCommand($this->plugin, "truce", "Truce with other factions"));
        $this->registerSubCommand(new UnallySubCommand($this->plugin, "unally", "End faction alliance"));
        $this->registerSubCommand(new UnbanSubCommand($this->plugin, "unban", "Unban a member from your faction"));
        $this->registerSubCommand(new UnclaimSubCommand($this->plugin, "unclaim", "Unclaim a chunk"));
    }
}