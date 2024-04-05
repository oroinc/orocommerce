<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data\ORM;

use Oro\Bundle\CMSBundle\Migrations\Data\AbstractLoadPageData;

/**
 * Loads the About landing page.
 */
class LoadPageData extends AbstractLoadPageData
{
    protected array $imagesMap = [
        'about' => [
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/320.jpg' =>
                '/bundles/orocms/images/landing-page/320.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/768.jpg' =>
                '/bundles/orocms/images/landing-page/768.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/1384.jpg' =>
                '/bundles/orocms/images/landing-page/1384.jpg',
        ]
    ];

    /**
     * {@inheritDoc}
     */
    protected function getFilePaths(): string
    {
        return $this->getFilePathsFromLocator('@OroCMSBundle/Migrations/Data/ORM/data/pages.yml');
    }
}
