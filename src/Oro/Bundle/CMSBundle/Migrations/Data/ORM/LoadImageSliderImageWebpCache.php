<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Gaufrette\Adapter as GaufretteAdapter;
use Gaufrette\File as GaufretteFile;
use Gaufrette\Filesystem as GaufretteFilesystem;
use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Demo fixture for loading demo image slider images webp cache.
 */
class LoadImageSliderImageWebpCache extends LoadImageSliderImageCache
{
    public function load(ObjectManager $manager): void
    {
        if ($this->container->get('oro_attachment.tools.webp_configuration')->isDisabled()) {
            return;
        }

        $this->doLoad($manager);
    }

    protected function getPathForFilteredImage(File $file, string $filter): string
    {
        return $this->getResizedImagePathProvider()->getPathForFilteredImage($file, $filter, 'webp');
    }

    protected function getImageFile(string $imageDir, string $filename): ?GaufretteFile
    {
        try {
            $filesystem = new GaufretteFilesystem(new GaufretteAdapter\Local($imageDir, false, 0600));
            $file = $filesystem->get($filename . '.webp');
        } catch (\Exception $e) {
            // Image not found
        }

        return $file ?? null;
    }
}
