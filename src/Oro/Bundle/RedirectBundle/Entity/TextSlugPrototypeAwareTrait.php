<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

trait TextSlugPrototypeAwareTrait
{
    /**
     * @var string
     *
     * @ORM\Column(name="text_slug_prototype", type="string", length=128, nullable=true)
     */
    protected $textSlugPrototype;

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
