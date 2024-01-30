<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Migrations\Data\AbstractLoadPageData;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

/**
 * Loads the About landing page.
 */
class LoadPageData extends AbstractLoadPageData
{
    private array $imagesMap = [
        'about' => [
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/320.jpg' =>
                '/bundles/orocms/images/landing-page/320.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/768.jpg' =>
                '/bundles/orocms/images/landing-page/768.jpg',
            '@OroCMSBundle/Migrations/Data/ORM/data/landing-page/1384.jpg' =>
                '/bundles/orocms/images/landing-page/1384.jpg',
        ],
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

    /**
     * {@inheritDoc}
     */
    protected function loadFromFile(ObjectManager $manager, string $filePath, Organization $organization): array
    {
        $pages = parent::loadFromFile($manager, $filePath, $organization);

        $fileManager = $this->container->get('oro_attachment.file_manager');
        foreach ($pages as $name => $page) {
            if (!isset($this->imagesMap[$name])) {
                continue;
            }

            foreach ($this->imagesMap[$name] as $source => $placeholder) {
                $parts = explode('/', $source);

                $digitalAsset = $this->createDigitalAsset(
                    $manager,
                    $fileManager,
                    $source,
                    sprintf('%s_%s', $name, array_pop($parts))
                );

                $manager->flush();

                $page->setContent(
                    str_replace(
                        $placeholder,
                        sprintf("{{ wysiwyg_image('%d','%s') }}", $digitalAsset->getId(), UUIDGenerator::v4()),
                        $page->getContent()
                    )
                );
            }
        }

        return $pages;
    }

    /**
     * {@inheritDoc}
     */
    protected function getFilePaths(): string
    {
        return $this->getFilePathsFromLocator('@OroCMSBundle/Migrations/Data/ORM/data/pages.yml');
    }
}
