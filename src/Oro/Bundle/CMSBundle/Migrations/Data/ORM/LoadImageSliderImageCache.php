<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Gaufrette\Adapter as GaufretteAdapter;
use Gaufrette\File as GaufretteFile;
use Gaufrette\Filesystem as GaufretteFilesystem;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImagePathProviderInterface;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\GaufretteBundle\FileManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Demo fixture for loading demo image slider images cache.
 */
class LoadImageSliderImageCache extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadImageSlider::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        if ($this->container->get('oro_attachment.tools.webp_configuration')->isEnabledForAll()) {
            return;
        }

        $this->doLoad($manager);
    }

    protected function doLoad(ObjectManager $manager): void
    {
        $imageSlides = $manager->getRepository(ImageSlide::class)->findAll();
        $locator = $this->container->get('file_locator');
        $imageDir = $locator->locate('@OroCMSBundle/Migrations/Data/ORM/data/promo-slider');
        $imageDir = is_array($imageDir) ? current($imageDir) : $imageDir;

        foreach ($imageSlides as $imageSlide) {
            foreach (['getMainImage', 'getMediumImage', 'getSmallImage'] as $getter) {
                $file = $imageSlide->$getter();
                if (!$file) {
                    continue;
                }

                $filename = $file->getOriginalFilename();
                $gaufretteFile = $this->getImageFile($imageDir, $filename);

                if ($gaufretteFile) {
                    $storagePath = $this->getPathForFilteredImage($file, 'original');
                    $this->getProtectedMediaCacheManager()->writeToStorage($gaufretteFile->getContent(), $storagePath);
                }
            }
        }
    }

    protected function getPathForFilteredImage(File $file, string $filter): string
    {
        return $this->getResizedImagePathProvider()->getPathForFilteredImage($file, $filter);
    }

    protected function getResizedImagePathProvider(): ResizedImagePathProviderInterface
    {
        return $this->container->get('oro_attachment.provider.resized_image_path');
    }

    protected function getProtectedMediaCacheManager(): FileManager
    {
        return $this->container->get('oro_attachment.manager.protected_mediacache');
    }

    protected function getImageFile(string $imageDir, string $filename): ?GaufretteFile
    {
        try {
            $filesystem = new GaufretteFilesystem(new GaufretteAdapter\Local($imageDir, false, 0600));
            $file = $filesystem->get($filename);
        } catch (\Exception $e) {
            // Image not found
        }

        return $file ?? null;
    }
}
