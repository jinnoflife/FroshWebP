<?php
/**
 * Created by PhpStorm.
 * User: jinnoflife
 * Date: 2019-03-29
 * Time: 17:44
 */

namespace FroshWebP\Components\ImageStack;

class Arguments
{
    /**
     * @var array
     */
    private $collectionsToUse = [];

    /**
     * @var array
     */
    private $collectionsToIgnore = [];

    /**
     * @var int
     */
    private $stack = 0;

    /**
     * @var int
     */
    private $offset = 0;

    /**
     * @var bool
     */
    private $force = false;

    /**
     * Arguments constructor.
     *
     * @param array $collectionsToUse
     * @param array $collectionsToIgnore
     * @param int   $stack
     * @param int   $offset
     */
    public function __construct(array $collectionsToUse, array $collectionsToIgnore, int $stack, int $offset, bool $force)
    {
        $this->collectionsToUse = $collectionsToUse;
        $this->collectionsToIgnore = $collectionsToIgnore;
        $this->stack = $stack;
        $this->offset = $offset;
        $this->force = $force;
    }

    /**
     * @return array
     */
    public function getCollectionsToUse(): array
    {
        return $this->collectionsToUse;
    }

    /**
     * @return array
     */
    public function getCollectionsToIgnore(): array
    {
        return $this->collectionsToIgnore;
    }

    /**
     * @return int
     */
    public function getStack(): int
    {
        return $this->stack;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return bool
     */
    public function isForce(): bool
    {
        return $this->force;
    }
}
