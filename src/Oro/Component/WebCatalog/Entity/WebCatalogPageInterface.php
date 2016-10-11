<?php

namespace Oro\Component\WebCatalog\Entity;

interface WebCatalogPageInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getType();
}
