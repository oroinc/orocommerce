<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data\ORM;

use Oro\Bundle\CMSBundle\Migrations\Data\AbstractLoadPageData;

class LoadPageData extends AbstractLoadPageData
{
    const CONTENT_US_TITLE = 'Contact Us';
    const ABOUT_TITLE = 'About';

    /**
     * {@inheritDoc}
     */
    protected function getFilePaths()
    {
        return $this->getFilePathsFromLocator('@OroCMSBundle/Migrations/Data/ORM/data/pages.yml');
    }
}
