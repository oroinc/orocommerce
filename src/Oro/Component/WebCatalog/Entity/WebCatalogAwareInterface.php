<?php

namespace Oro\Component\WebCatalog\Entity;

interface WebCatalogAwareInterface
{
    /**
     * @return WebCatalogInterface
     */
    public function getWebCatalog();
}
