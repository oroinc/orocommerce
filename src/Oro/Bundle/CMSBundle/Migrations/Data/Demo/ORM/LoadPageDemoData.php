<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data\Demo\ORM;

use Oro\Bundle\CMSBundle\Migrations\Data\AbstractLoadPageData;

class LoadPageDemoData extends AbstractLoadPageData
{
    /**
     * {@inheritDoc}
     */
    protected function getFilePaths()
    {
        return $this->getFilePathsFromLocator('@OroCMSBundle/Migrations/Data/Demo/ORM/data/pages.yml');
    }
}
