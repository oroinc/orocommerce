<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Entity\SluggableTrait;

class SluggableEntityStub implements SluggableInterface
{
    use SluggableTrait;

    /**
     * @var int
     */
    protected $id;

    public function __construct()
    {
        $this->slugs = new ArrayCollection();
        $this->slugPrototypes = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return SluggableEntityStub
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
