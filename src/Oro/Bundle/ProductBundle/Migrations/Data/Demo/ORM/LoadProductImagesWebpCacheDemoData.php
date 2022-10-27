<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Doctrine\Persistence\ObjectManager;
use Gaufrette\File as GaufretteFile;
use Gaufrette\Filesystem as GaufretteFilesystem;
use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Demo fixture for loading demo product images webp cache.
 */
class LoadProductImagesWebpCacheDemoData extends AbstractLoadProductImagesCacheDemoData
{
    public function load(ObjectManager $manager): void
    {
        if ($this->container->get('oro_attachment.tools.webp_configuration')->isDisabled()) {
            return;
        }

        parent::load($manager);
    }

    protected function getPathForFilteredImage(File $file, string $filter): string
    {
        return $this->getResizedImagePathProvider()->getPathForFilteredImage($file, $filter, 'webp');
    }

    protected function getResizedProductImageFile(
        GaufretteFilesystem $filesystem,
        string $sku,
        string $name
    ): ?GaufretteFile {
        $file = null;

        try {
            $file = $filesystem->get(sprintf('%s.jpeg.webp', $this->getImageFileName($sku, $name)));
        } catch (\Exception $e) {
            //image not found
        }

        return $file;
    }
}
