<?php

declare(strict_types=1);

namespace juqn\entity\object;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\Fallable;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityBlockChangeEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\ItemFactory;
use pocketmine\level\Position;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\IntTag;

/**
 * Class FallingBlock
 * @package juqn\entity\object
 */
class FallingBlock extends Entity
{

    /** @var int */
    public const NETWORK_ID = self::FALLING_BLOCK;

    /** @var float */
    public $width = 0.98;
    /** @var float */
    public $height = 0.98;

    /** @var float */
    protected $baseOffset = 0.49;

    /** @var float */
    protected $gravity = 0.04;
    /** @var float */
    protected $drag = 0.02;

    /** @var Block */
    protected $block;

    /** @var bool */
    public $canCollide = false;

    protected function initEntity(): void
    {
        parent::initEntity();
        $blockId = 0;

        if ($this->namedtag->hasTag("TileID", IntTag::class))
            $blockId = $this->namedtag->getInt("TileID");
        elseif ($this->namedtag->hasTag("Tile", ByteTag::class)) {
            $blockId = $this->namedtag->getByte("Tile");
            $this->namedtag->removeTag("Tile");
        }

        if ($blockId === 0) {
            $this->close();
            return;
        }
        $damage = $this->namedtag->getByte("Data", 0);
        $this->block = BlockFactory::get($blockId, $damage);
        $this->propertyManager->setInt(self::DATA_VARIANT, $this->block->getRuntimeId());
    }

    /**
     * @param Entity $entity
     * @return bool
     */
    public function canCollideWith(Entity $entity): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function canBeMovedByCurrents(): bool
    {
        return false;
    }

    /**
     * @param EntityDamageEvent $source
     */
    public function attack(EntityDamageEvent $source): void
    {
        if ($source->getCause() === EntityDamageEvent::CAUSE_VOID)
            parent::attack($source);
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        if ($this->closed)
            return false;
        $hasUpdate = parent::entityBaseTick($tickDiff);

        if (!$this->isFlaggedForDespawn()) {
            $pos = Position::fromObject($this->add(-$this->width / 2, $this->height, -$this->width / 2)->floor(), $this->getLevelNonNull());
            $this->block->position($pos);
            $blockTarget = null;

            if ($this->block instanceof Fallable)
                $blockTarget = $this->block->tickFalling();

            if ($this->onGround or $blockTarget !== null) {
                $this->flagForDespawn();
                $block = $this->level->getBlock($pos);

                if (!$block->canBeReplaced() or ($this->onGround and abs($this->y - $this->getFloorY()) > 0.001))
                    $this->getLevelNonNull()->dropItem($this, ItemFactory::get($this->getBlock(), $this->getDamage()));
                else {
                    $ev = new EntityBlockChangeEvent($this, $block, $blockTarget ?? $this->block);
                    $ev->call();

                    if (!$ev->isCancelled())
                        $this->getLevelNonNull()->setBlock($pos, $ev->getTo(), true);
                }
                $hasUpdate = true;
            }
        }
        return $hasUpdate;
    }

    /**
     * @return int
     */
    public function getBlock(): int
    {
        return $this->block->getId();
    }

    /**
     * @return int
     */
    public function getDamage(): int
    {
        return $this->block->getDamage();
    }

    public function saveNBT(): void
    {
        parent::saveNBT();
        $this->namedtag->setInt("TileID", $this->block->getId(), true);
        $this->namedtag->setByte("Data", $this->block->getDamage());
    }
}