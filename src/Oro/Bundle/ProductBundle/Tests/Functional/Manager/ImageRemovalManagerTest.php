<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Manager;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tests\Functional\Manager\ImageRemovalManagerTestingTrait;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ImageRemovalManagerTest extends WebTestCase
{
    use ImageRemovalManagerTestingTrait;
    use ConfigManagerAwareTestTrait;

    private const PRODUCT_ORIGINAL_FILE_NAMES_ENABLED = 'oro_product.original_file_names_enabled';

    /** @var bool */
    private $initialOriginalFileNames;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->initialOriginalFileNames = self::getConfigManager('global')
            ->get(self::PRODUCT_ORIGINAL_FILE_NAMES_ENABLED);
    }

    protected function tearDown(): void
    {
        $this->setOriginalFileNames($this->initialOriginalFileNames);
    }

    private function setOriginalFileNames(bool $enabled): void
    {
        $configManager = self::getConfigManager(null);
        if ($configManager->get(self::PRODUCT_ORIGINAL_FILE_NAMES_ENABLED) !== $enabled) {
            $configManager->set(self::PRODUCT_ORIGINAL_FILE_NAMES_ENABLED, $enabled);
            $configManager->flush();
        }
    }

    private function createProductImage(): File
    {
        $file = $this->createFileEntity();
        $file->setParentEntityClass(ProductImage::class);
        $file->setParentEntityId(1);
        $file->setParentEntityFieldName('image');
        $this->saveFileEntity($file);

        return $file;
    }

    public function testRemoveFilesWhenOriginalFileNamesDisabled(): void
    {
        $file = $this->createProductImage();

        $this->setOriginalFileNames(false);
        $this->applyImageFilter($file, 'product_small');
        $this->applyImageFilter($file, 'product_medium');

        $fileNames = $this->getImageFileNames($file);
        self::assertCount(2, $fileNames);

        $this->removeFiles($file);
        $this->assertFilesDoNotExist($file, $fileNames);
    }

    public function testRemoveFilesWhenOriginalFileNamesEnabled(): void
    {
        $file = $this->createProductImage();

        $this->setOriginalFileNames(true);
        $this->applyImageFilter($file, 'product_small');
        $this->applyImageFilter($file, 'product_medium');

        $fileNames = $this->getImageFileNames($file);
        self::assertCount(2, $fileNames);
        foreach ($fileNames as $fileName) {
            if (strpos($fileName, 'attachment/filter/') === 0) {
                self::assertStringEndsWith('-original_attachment.jpg', $fileName);
            }
        }

        $this->removeFiles($file);
        $this->assertFilesDoNotExist($file, $fileNames);
    }

    public function testRemoveFilesWhenOriginalFileNamesDisabledButThereAreFilesCreatedWhenItWasEnabled(): void
    {
        $file = $this->createProductImage();

        $this->setOriginalFileNames(true);
        $this->applyImageFilter($file, 'product_small');
        $this->applyImageFilter($file, 'product_medium');
        $fileNames = $this->getImageFileNames($file);

        $this->setOriginalFileNames(false);
        $this->applyImageFilter($file, 'product_small');
        $this->applyImageFilter($file, 'product_medium');

        $fileNames = array_unique(array_merge($fileNames, $this->getImageFileNames($file)));
        self::assertCount(4, $fileNames);

        $this->removeFiles($file);
        $this->assertFilesDoNotExist($file, $fileNames);
    }

    public function testRemoveFilesWhenOriginalFileNamesEnabledButThereAreFilesCreatedWhenItWasDisabled(): void
    {
        $file = $this->createProductImage();

        $this->setOriginalFileNames(false);
        $this->applyImageFilter($file, 'product_small');
        $this->applyImageFilter($file, 'product_medium');
        $fileNames = $this->getImageFileNames($file);

        $this->setOriginalFileNames(true);
        $this->applyImageFilter($file, 'product_small');
        $this->applyImageFilter($file, 'product_medium');

        $fileNames = array_unique(array_merge($fileNames, $this->getImageFileNames($file)));
        self::assertCount(4, $fileNames);

        $this->removeFiles($file);
        $this->assertFilesDoNotExist($file, $fileNames);
    }
}
