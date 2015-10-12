<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Storage;

class CategoryVisibilityData
{
    /** @var array */
    protected $ids = [];

    /** @var bool */
    protected $visible;

    /**
     * @param array $ids
     * @param bool $visible
     */
    public function __construct(array $ids, $visible)
    {
        $this->ids = $ids;
        $this->visible = $visible;
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return (bool)$this->visible;
    }

    /**
     * @return array
     */
    public function getIds()
    {
        return $this->ids;
    }
}
