<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* BaseVisibilityResolved abstract class
*
*/
#[ORM\MappedSuperclass]
abstract class BaseVisibilityResolved
{
    const VISIBILITY_HIDDEN = -1;
    const VISIBILITY_VISIBLE = 1;
    const VISIBILITY_FALLBACK_TO_CONFIG = 0; // fallback to category config value

    #[ORM\Column(name: 'visibility', type: Types::SMALLINT, nullable: true)]
    protected ?int $visibility = null;

    #[ORM\Column(name: 'source', type: Types::SMALLINT, nullable: true)]
    protected ?int $source = null;

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
