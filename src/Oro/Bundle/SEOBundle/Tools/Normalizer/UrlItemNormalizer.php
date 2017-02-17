<?php

namespace Oro\Bundle\SEOBundle\Tools\Normalizer;

use Oro\Component\SEO\Model\DTO\UrlItemInterface;

class UrlItemNormalizer
{
    /**
     * @param UrlItemInterface $urlItem
     * @return array
     */
    public function normalize(UrlItemInterface $urlItem)
    {
        return array_filter([
            'loc' => $urlItem->getLocation(),
            'changefreq' => $urlItem->getChangeFrequency(),
            'priority' => $urlItem->getPriority(),
            'lastmod' => $urlItem->getLastModification() 
                ? $urlItem->getLastModification()->format(\DateTime::W3C)
                : null,
        ]);
    }
}
