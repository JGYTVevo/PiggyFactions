<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyFactions\event\management;

use DaPigGuy\PiggyFactions\event\FactionEvent;
use DaPigGuy\PiggyFactions\factions\Faction;
use pocketmine\event\Cancellable;

class FactionRenameEvent extends FactionEvent implements Cancellable
{
    /** @var string */
    private $name;

    public function __construct(Faction $faction, string $name)
    {
        parent::__construct($faction);
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}