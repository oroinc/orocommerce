<?php

namespace Oro\Component\WebCatalog\Entity;

use Doctrine\Common\Collections\Collection;

interface ContentNodeInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return ContentNodeInterface[]
     */
    public function getContentVariants();

    /**
     * @return Collection
     */
    public function getTitles();

    /**
     * @return boolean
     */
    public function isRewriteVariantTitle();
}
