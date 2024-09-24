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
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/illustration-carts-order-history.png' =>
                '/bundles/orocms/images/home-page/illustration-carts-order-history.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/illustration-carts-order-history-1280.png' =>
                '/bundles/orocms/images/home-page/illustration-carts-order-history-1280.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/illustration-carts-contact-us.png' =>
                '/bundles/orocms/images/home-page/illustration-carts-contact-us.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/illustration-carts-contact-us-1280.png' =>
                '/bundles/orocms/images/home-page/illustration-carts-contact-us-1280.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-1.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-1.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-1-640.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-1-640.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-1-992.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-1-992.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-2.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-2.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-2-640.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-2-640.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-2-992.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-2-992.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-2-1280.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-2-1280.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-3.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-3.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-3-640.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-3-640.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-3-992.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-3-992.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-4.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-4.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-4-640.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-4-640.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-5.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-5.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-5-640.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-5-640.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-5-992.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-5-992.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-6.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-6.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-6-640.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-6-640.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-6-992.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-6-992.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-7.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-7.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-7-640.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-7-640.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-7-992.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-7-992.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-8.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-8.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-8-640.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-8-640.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-8-992.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-8-992.png',
            '@OroCMSBundle/Migrations/Data/ORM/data/home-page/featured-categories-grid-img-8-1280.png' =>
                '/bundles/orocms/images/home-page/featured-categories-grid-img-8-1280.png',
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
