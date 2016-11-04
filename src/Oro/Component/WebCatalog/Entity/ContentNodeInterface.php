<?php

namespace Oro\Component\WebCatalog\Entity;

interface ContentNodeInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getName();
}
