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

    private const ATTACHMENT_ORIGINAL_FILE_NAMES_ENABLED = 'oro_attachment.original_file_names_enabled';

    /** @var bool */
    private $productInitialOriginalFileNames;

    /** @var bool */
    private $attachmentInitialOriginalFileNames;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $globalConfigManager = self::getConfigManager();
        $this->productInitialOriginalFileNames = $globalConfigManager
            ->get(self::PRODUCT_ORIGINAL_FILE_NAMES_ENABLED);
        $this->attachmentInitialOriginalFileNames = $globalConfigManager
            ->get(self::ATTACHMENT_ORIGINAL_FILE_NAMES_ENABLED);

        $this->setOriginalFileNames(self::ATTACHMENT_ORIGINAL_FILE_NAMES_ENABLED, false);
    }

    protected function tearDown(): void
    {
        $this->setOriginalFileNames(
            self::PRODUCT_ORIGINAL_FILE_NAMES_ENABLED,
            $this->productInitialOriginalFileNames
        );
        $this->setOriginalFileNames(
            self::ATTACHMENT_ORIGINAL_FILE_NAMES_ENABLED,
            $this->attachmentInitialOriginalFileNames
        );
    }

    private function setOriginalFileNames(string $configName, bool $enabled): void
    {
        $configManager = self::getConfigManager(null);
        if ($configManager->get($configName) !== $enabled) {
            $configManager->set($configName, $enabled);
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

        $this->setOriginalFileNames(self::PRODUCT_ORIGINAL_FILE_NAMES_ENABLED, false);
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

        $this->setOriginalFileNames(self::PRODUCT_ORIGINAL_FILE_NAMES_ENABLED, true);
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

        $this->setOriginalFileNames(self::PRODUCT_ORIGINAL_FILE_NAMES_ENABLED, true);
        $this->applyImageFilter($file, 'product_small');
        $this->applyImageFilter($file, 'product_medium');
        $fileNames = $this->getImageFileNames($file);

        $this->setOriginalFileNames(self::PRODUCT_ORIGINAL_FILE_NAMES_ENABLED, false);
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

        $this->setOriginalFileNames(self::PRODUCT_ORIGINAL_FILE_NAMES_ENABLED, false);
        $this->applyImageFilter($file, 'product_small');
        $this->applyImageFilter($file, 'product_medium');
        $fileNames = $this->getImageFileNames($file);

        $this->setOriginalFileNames(self::PRODUCT_ORIGINAL_FILE_NAMES_ENABLED, true);
        $this->applyImageFilter($file, 'product_small');
        $this->applyImageFilter($file, 'product_medium');

        $fileNames = array_unique(array_merge($fileNames, $this->getImageFileNames($file)));
        self::assertCount(4, $fileNames);

        $this->removeFiles($file);
        $this->assertFilesDoNotExist($file, $fileNames);
    }
}
