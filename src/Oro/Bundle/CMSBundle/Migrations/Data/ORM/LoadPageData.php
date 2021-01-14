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
    /** @var array */
    private $imagesMap = [
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    protected function getFilePaths()
    {
        return $this->getFilePathsFromLocator('@OroCMSBundle/Migrations/Data/ORM/data/pages.yml');
    }
}
