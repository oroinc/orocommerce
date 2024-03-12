<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* TextSlugPrototypeAware trait
*
*/
trait TextSlugPrototypeAwareTrait
{
    #[ORM\Column(name: 'text_slug_prototype', type: Types::STRING, length: 128, nullable: true)]
    protected ?string $textSlugPrototype = null;

    /**
     * @return string
     */
    public function getTextSlugPrototype()
    {
        return $this->textSlugPrototype;
    }

    /**
     * @param string $textSlugPrototype
     *
     * @return TextSlugPrototypeAwareTrait
     */
    public function setTextSlugPrototype($textSlugPrototype)
    {
        $this->textSlugPrototype = $textSlugPrototype;

        return $this;
    }
}
