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
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/1_360.webp' =>
                '/bundles/orocms/images/landing-page/1_360.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/1_360-2x.webp' =>
                '/bundles/orocms/images/landing-page/1_360-2x.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/1_768.webp' =>
                '/bundles/orocms/images/landing-page/1_768.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/1_768-2x.webp' =>
                '/bundles/orocms/images/landing-page/1_768-2x.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/1_1280.webp' =>
                '/bundles/orocms/images/landing-page/1_1280.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/1_1280-2x.webp' =>
                '/bundles/orocms/images/landing-page/1_1280-2x.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/1_1920.webp' =>
                '/bundles/orocms/images/landing-page/1_1920.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/1_1920-2x.webp' =>
                '/bundles/orocms/images/landing-page/1_1920-2x.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/2_360.webp' =>
                '/bundles/orocms/images/landing-page/2_360.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/2_360-2x.webp' =>
                '/bundles/orocms/images/landing-page/2_360-2x.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/2_768.webp' =>
                '/bundles/orocms/images/landing-page/2_768.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/2_768-2x.webp' =>
                '/bundles/orocms/images/landing-page/2_768-2x.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/2_1280.webp' =>
                '/bundles/orocms/images/landing-page/2_1280.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/2_1280-2x.webp' =>
                '/bundles/orocms/images/landing-page/2_1280-2x.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/2_1920.webp' =>
                '/bundles/orocms/images/landing-page/2_1920.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/2_1920-2x.webp' =>
                '/bundles/orocms/images/landing-page/2_1920-2x.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/3_360.webp' =>
                '/bundles/orocms/images/landing-page/3_360.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/3_360-2x.webp' =>
                '/bundles/orocms/images/landing-page/3_360-2x.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/3_768.webp' =>
                '/bundles/orocms/images/landing-page/3_768.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/3_768-2x.webp' =>
                '/bundles/orocms/images/landing-page/3_768-2x.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/3_1280.webp' =>
                '/bundles/orocms/images/landing-page/3_1280.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/3_1280-2x.webp' =>
                '/bundles/orocms/images/landing-page/3_1280-2x.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/3_1920.webp' =>
                '/bundles/orocms/images/landing-page/3_1920.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/3_1920-2x.webp' =>
                '/bundles/orocms/images/landing-page/3_1920-2x.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/1920_map.webp' =>
                '/bundles/orocms/images/landing-page/1920_map.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/1280_map.webp' =>
                '/bundles/orocms/images/landing-page/1280_map.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/768_map.webp' =>
                '/bundles/orocms/images/landing-page/768_map.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/360_map.webp' =>
                '/bundles/orocms/images/landing-page/360_map.webp'
        ]
    ];

    #[\Override]
    protected function getFilePaths(): string
    {
        return $this->getFilePathsFromLocator('@OroCMSBundle/Migrations/Data/ORM/data/pages.yml');
    }
}
