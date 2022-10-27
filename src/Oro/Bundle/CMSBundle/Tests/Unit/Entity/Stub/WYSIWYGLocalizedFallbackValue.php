<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;

/**
 * @ORM\Table
 * @ORM\Entity
 * @Config
 */
class WYSIWYGLocalizedFallbackValue extends AbstractLocalizedFallbackValue
{
    /**
     * @var string|null
     *
     * @ORM\Column(type="wysiwyg", nullable=true)
     */
    private $wysiwyg;

    /**
     * @var string|null
     *
     * @ORM\Column(type="wysiwyg_style", name="wysiwyg_style", nullable=true)
     */
    private $wysiwygStyle;

    /**
     * @var array|null
     *
     * @ORM\Column(type="wysiwyg_properties", name="wysiwyg_properties", nullable=true)
     */
    private $wysiwygProperties;

    public function getWysiwyg(): ?string
    {
        return $this->wysiwyg;
    }

    /**
     * @param string|null $wysiwyg
     */
    public function setWysiwyg($wysiwyg): void
    {
        $this->wysiwyg = $wysiwyg;
    }

    public function getWysiwygStyle(): ?string
    {
        return $this->wysiwygStyle;
    }

    /**
     * @param string|null $wysiwygStyle
     */
    public function setWysiwygStyle($wysiwygStyle): void
    {
        $this->wysiwygStyle = $wysiwygStyle;
    }

    public function getWysiwygProperties(): ?array
    {
        return $this->wysiwygProperties;
    }

    /**
     * @param array|null $wysiwygProperties
     */
    public function setWysiwygProperties($wysiwygProperties): void
    {
        $this->wysiwygProperties = $wysiwygProperties;
    }
}
