<?php

namespace OroB2B\Bundle\CMSBundle\Migrations\Data\ORM;

use OroB2B\Bundle\CMSBundle\Migrations\Data\AbstractLoadPageData;

class LoadPageData extends AbstractLoadPageData
{
    const CONTENT_US_TITLE = 'Contact Us';
    const ABOUT_TITLE = 'About';

    /**
     * {@inheritDoc}
     */
    protected function getFilePaths()
    {
        return $this->getFilePathsFromLocator('@OroB2BCMSBundle/Migrations/Data/ORM/data/pages.yml');
    }
}
