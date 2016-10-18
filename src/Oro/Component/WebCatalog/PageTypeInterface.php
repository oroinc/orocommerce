<?php

namespace Oro\Component\WebCatalog;

use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

interface PageTypeInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @return string
     */
    public function getFormType();

    /**
     * @return bool
     */
    public function isAllowed();

    /**
     * @param ContentVariantInterface $page
     * @return bool
     */
    public function isSupportedPage(ContentVariantInterface $page);

    /**
     * @param ContentVariantInterface $page
     * @return RouteData
     */
    public function getRouteData(ContentVariantInterface $page);
}
