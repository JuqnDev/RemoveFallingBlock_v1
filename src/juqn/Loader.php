<?php

declare(strict_types=1);

namespace juqn;

use juqn\entity\object\FallingBlock;
use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;

/**
 * Class Loader
 * @package juqn
 */
class Loader extends PluginBase
{

    public function onEnable()
    {
        # Replace entity
        Entity::registerEntity(FallingBlock::class, false, ['FallingSand', 'minecraft:falling_block']);
    }
}