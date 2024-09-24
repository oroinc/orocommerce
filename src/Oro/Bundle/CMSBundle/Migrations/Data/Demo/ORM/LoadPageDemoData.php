<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data\Demo\ORM;

use Oro\Bundle\CMSBundle\Migrations\Data\AbstractLoadPageData;

/**
 * Loads some "lorem ipsum" pages.
 */
class LoadPageDemoData extends AbstractLoadPageData
{
    #[\Override]
    protected function getFilePaths(): string
    {
        return $this->getFilePathsFromLocator('@OroCMSBundle/Migrations/Data/Demo/ORM/data/pages.yml');
    }
}
