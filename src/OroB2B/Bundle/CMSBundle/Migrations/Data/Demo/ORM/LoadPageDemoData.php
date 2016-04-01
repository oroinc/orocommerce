<?php

namespace OroB2B\Bundle\CMSBundle\Migrations\Data\Demo\ORM;

use OroB2B\Bundle\CMSBundle\Migrations\Data\AbstractLoadPageData;

class LoadPageDemoData extends AbstractLoadPageData
{
    /**
     * {@inheritDoc}
     */
    protected function getFilePaths()
    {
        return $this->getFilePathsFromLocator('@OroB2BCMSBundle/Migrations/Data/Demo/ORM/data/pages.yml');
    }
}
