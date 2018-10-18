<?php

namespace Oro\Bundle\WebCatalogBundle\Layout\DataProvider;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

/**
 * Returns web catalog node title and page title based on current content node.
 */
interface TitleDataProviderInterface
{
    /**
     * Return web catalog node title
     *
     * @param string $default
     *
     * @return LocalizedFallbackValue|string
     */
    public function getNodeTitle($default = '');

    /**
     * Return web catalog page title
     *
     * @param string $default
     * @param object|null $data
     *
     * @return LocalizedFallbackValue|string
     */
    public function getTitle($default = '', $data = null);
}
