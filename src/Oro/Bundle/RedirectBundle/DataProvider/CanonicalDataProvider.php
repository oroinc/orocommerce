<?php

namespace Oro\Bundle\RedirectBundle\DataProvider;

use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;

/**
 * Provides canonical URLs based on the given website and website configuration.
 */
class CanonicalDataProvider
{
    public function __construct(
        private CanonicalUrlGenerator $canonicalUrlGenerator
    ) {
    }

    public function getUrl(SluggableInterface $data): string
    {
        return $this->canonicalUrlGenerator->getUrl($data);
    }

    public function getHomePageUrl(): string
    {
        return $this->canonicalUrlGenerator->getAbsoluteUrl('/');
    }
}
