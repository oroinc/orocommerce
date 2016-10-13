<?php

namespace Oro\Component\WebCatalog;

use Oro\Component\WebCatalog\Entity\WebCatalogPageInterface;

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
     * @param WebCatalogPageInterface $page
     * @return bool
     */
    public function isSupportedPage(WebCatalogPageInterface $page);

    /**
     * @param WebCatalogPageInterface $page
     * @return RouteData
     */
    public function getRouteData(WebCatalogPageInterface $page);
}
