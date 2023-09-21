<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Resolver\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\LocaleBundle\Entity\FallbackTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

class ConsentStub extends Consent
{
    use FallbackTrait;

    /** @var ArrayCollection */
    protected $names;

    public function getName(Localization $localization = null): LocalizedFallbackValue
    {
        return $this->getFallbackValue($this->names, $localization);
    }

    public function setNames(ArrayCollection $names): void
    {
        $this->names = $names;
    }
}
