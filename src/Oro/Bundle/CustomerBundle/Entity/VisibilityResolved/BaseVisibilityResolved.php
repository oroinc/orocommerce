<?php

namespace Oro\Bundle\CustomerBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 */
abstract class BaseVisibilityResolved
{
    const VISIBILITY_HIDDEN = -1;
    const VISIBILITY_VISIBLE = 1;
    const VISIBILITY_FALLBACK_TO_CONFIG = 0; // fallback to category config value

    /**
     * @var int
     *
     * @ORM\Column(name="visibility", type="smallint", nullable=true)
     */
    protected $visibility;

    /**
     * @var int
     *
     * @ORM\Column(name="source", type="smallint", nullable=true)
     */
    protected $source;

    /**
     * @param $visibility
     * @return $this
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * @return int
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @return int
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param int $source
     * @return $this
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }
}
