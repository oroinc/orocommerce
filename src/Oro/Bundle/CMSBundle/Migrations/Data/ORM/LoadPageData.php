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
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/1_360.jpg' =>
                '/bundles/orocms/images/landing-page/1_360.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/1_360-2x.jpg' =>
                '/bundles/orocms/images/landing-page/1_360-2x.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/1_768.jpg' =>
                '/bundles/orocms/images/landing-page/1_768.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/1_768-2x.jpg' =>
                '/bundles/orocms/images/landing-page/1_768-2x.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/1_1280.jpg' =>
                '/bundles/orocms/images/landing-page/1_1280.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/1_1280-2x.jpg' =>
                '/bundles/orocms/images/landing-page/1_1280-2x.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/1_1920.jpg' =>
                '/bundles/orocms/images/landing-page/1_1920.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/1_1920-2x.jpg' =>
                '/bundles/orocms/images/landing-page/1_1920-2x.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/2_360.jpg' =>
                '/bundles/orocms/images/landing-page/2_360.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/2_360-2x.jpg' =>
                '/bundles/orocms/images/landing-page/2_360-2x.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/2_768.jpg' =>
                '/bundles/orocms/images/landing-page/2_768.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/2_768-2x.jpg' =>
                '/bundles/orocms/images/landing-page/2_768-2x.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/2_1280.jpg' =>
                '/bundles/orocms/images/landing-page/2_1280.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/2_1280-2x.jpg' =>
                '/bundles/orocms/images/landing-page/2_1280-2x.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/2_1920.jpg' =>
                '/bundles/orocms/images/landing-page/2_1920.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/2_1920-2x.jpg' =>
                '/bundles/orocms/images/landing-page/2_1920-2x.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/3_360.jpg' =>
                '/bundles/orocms/images/landing-page/3_360.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/3_360-2x.jpg' =>
                '/bundles/orocms/images/landing-page/3_360-2x.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/3_768.jpg' =>
                '/bundles/orocms/images/landing-page/3_768.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/3_768-2x.jpg' =>
                '/bundles/orocms/images/landing-page/3_768-2x.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/3_1280.jpg' =>
                '/bundles/orocms/images/landing-page/3_1280.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/3_1280-2x.jpg' =>
                '/bundles/orocms/images/landing-page/3_1280-2x.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/3_1920.jpg' =>
                '/bundles/orocms/images/landing-page/3_1920.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/3_1920-2x.jpg' =>
                '/bundles/orocms/images/landing-page/3_1920-2x.jpg'
        ]
    ];

    #[\Override]
    protected function getFilePaths(): string
    {
        return $this->getFilePathsFromLocator('@OroCMSBundle/Migrations/Data/ORM/data/pages.yml');
    }
}
