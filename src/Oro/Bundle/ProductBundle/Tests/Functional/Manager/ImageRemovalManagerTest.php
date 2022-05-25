<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Manager;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tests\Functional\Manager\ImageRemovalManagerTestingTrait;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\MessageQueueBundle\Consumption\CacheState;
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

    private CacheState $cacheState;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $globalConfigManager = self::getConfigManager();
        $this->productInitialOriginalFileNames = $globalConfigManager
            ->get(self::PRODUCT_ORIGINAL_FILE_NAMES_ENABLED);
        $this->attachmentInitialOriginalFileNames = $globalConfigManager
            ->get(self::ATTACHMENT_ORIGINAL_FILE_NAMES_ENABLED);
        $this->cacheState = self::getContainer()->get('oro_message_queue.consumption.cache_state');

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
        self::assertCount(4, $fileNames);

        $cacheChangeDate = $this->cacheState->getChangeDate();

        $this->removeFiles($file);
        $this->assertFilesDoNotExist($file, $fileNames);

        self::assertEquals($cacheChangeDate, $this->cacheState->getChangeDate());
    }

    public function testRemoveFilesWhenOriginalFileNamesEnabled(): void
    {
        $file = $this->createProductImage();

        $this->setOriginalFileNames(self::PRODUCT_ORIGINAL_FILE_NAMES_ENABLED, true);
        $this->applyImageFilter($file, 'product_small');
        $this->applyImageFilter($file, 'product_medium');

        $fileNames = $this->getImageFileNames($file);
        self::assertCount(4, $fileNames);
        foreach ($fileNames as $fileName) {
            if (str_starts_with($fileName, 'attachment/filter/')) {
                if (str_ends_with($fileName, '.jpg')) {
                    self::assertStringEndsWith('-original_attachment.jpg', $fileName);
                } else {
                    self::assertStringEndsWith('-original_attachment.jpg.webp', $fileName);
                }
            }
        }

        $cacheChangeDate = $this->cacheState->getChangeDate();

        $this->removeFiles($file);
        $this->assertFilesDoNotExist($file, $fileNames);

        self::assertEquals($cacheChangeDate, $this->cacheState->getChangeDate());
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
        self::assertCount(8, $fileNames);

        $cacheChangeDate = $this->cacheState->getChangeDate();

        $this->removeFiles($file);
        $this->assertFilesDoNotExist($file, $fileNames);

        self::assertEquals($cacheChangeDate, $this->cacheState->getChangeDate());
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
        self::assertCount(8, $fileNames);

        $cacheChangeDate = $this->cacheState->getChangeDate();

        $this->removeFiles($file);
        $this->assertFilesDoNotExist($file, $fileNames);

        self::assertEquals($cacheChangeDate, $this->cacheState->getChangeDate());
    }
}
