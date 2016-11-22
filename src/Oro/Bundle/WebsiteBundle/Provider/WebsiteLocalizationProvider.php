<?php

namespace Oro\Bundle\WebsiteBundle\Provider;

use Oro\Bundle\WebsiteBundle\Entity\Website;

class WebsiteLocalizationProvider extends AbstractWebsiteLocalizationProvider
{
    /**
     * {@inheritdoc}
     */
    public function getLocalizations(Website $website)
    {
        return $this->localizationManager->getLocalizations($this->getEnabledLocalizationIds());
    }
}
