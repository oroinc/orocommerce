<?php

namespace OroB2B\Bundle\AccountBundle\Storage;

class CategoryVisibilityData
{
    /** @var  bool */
    protected $visible;

    /** @var array  */
    protected $ids = [];

    /**
     * @param bool $visible
     * @param array $ids
     */
    public function __construct($visible, array $ids)
    {
        $this->visible = $visible;
        $this->ids = $ids;
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
