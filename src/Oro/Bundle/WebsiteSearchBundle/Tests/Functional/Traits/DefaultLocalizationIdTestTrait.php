<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits;

trait DefaultLocalizationIdTestTrait
{
    /**
     * @return int
     */
    protected function getDefaultLocalizationId()
    {
        return $this
            ->getContainer()
            ->get('oro_locale.manager.localization')
            ->getDefaultLocalization()
            ->getId();
    }
}
