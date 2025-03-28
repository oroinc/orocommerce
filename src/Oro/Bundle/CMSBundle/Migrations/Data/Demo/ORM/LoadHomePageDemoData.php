<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data\Demo\ORM;

use Oro\Bundle\CMSBundle\Migrations\Data\AbstractLoadPageData;

/**
 * Updates the Homepage content.
 */
class LoadHomePageDemoData extends AbstractLoadPageData
{
    protected array $imagesMap = [
        'homepage' => [
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/illustration-carts-order-history.webp' =>
                '/bundles/orocms/images/home-page/illustration-carts-order-history.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/illustration-carts-order-history-1280.webp' =>
                '/bundles/orocms/images/home-page/illustration-carts-order-history-1280.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/illustration-carts-contact-us.webp' =>
                '/bundles/orocms/images/home-page/illustration-carts-contact-us.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/illustration-carts-contact-us-1280.webp' =>
                '/bundles/orocms/images/home-page/illustration-carts-contact-us-1280.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-1.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-1.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-1-640.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-1-640.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-1-992.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-1-992.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-2.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-2.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-2-640.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-2-640.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-2-992.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-2-992.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-2-1280.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-2-1280.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-3.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-3.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-3-640.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-3-640.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-3-992.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-3-992.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-4.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-4.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-4-640.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-4-640.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-5.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-5.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-5-640.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-5-640.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-5-992.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-5-992.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-6.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-6.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-6-640.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-6-640.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-6-992.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-6-992.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-7.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-7.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-7-640.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-7-640.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-7-992.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-7-992.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-8.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-8.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-8-640.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-8-640.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-8-992.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-8-992.webp',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-8-1280.webp' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-8-1280.webp',
        ]
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return array_merge(
            parent::getDependencies(),
            [
                LoadImageSliderDemoData::class,
            ]
        );
    }

    #[\Override]
    protected function getFilePaths(): string
    {
        return $this->getFilePathsFromLocator('@OroCMSBundle/Migrations/Data/Demo/ORM/data/homepage.yml');
    }
}
