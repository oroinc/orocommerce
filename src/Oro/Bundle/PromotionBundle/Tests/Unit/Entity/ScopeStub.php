<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity;

use Oro\Bundle\ScopeBundle\Entity\Scope;

class ScopeStub extends Scope
{
    /**
     * @var string
     */
    private $locale;

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     * @return ScopeStub
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }
}
