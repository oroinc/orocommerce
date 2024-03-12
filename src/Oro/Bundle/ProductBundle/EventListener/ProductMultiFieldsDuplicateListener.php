<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\Helper\FieldConfigHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * Responsible for duplicate fields of the "multiple" type.
 */
class ProductMultiFieldsDuplicateListener
{
    public function __construct(
        private ConfigProvider $configProvider,
        private ManagerRegistry $managerRegistry
    ) {
    }

    public function onDuplicateAfter(ProductDuplicateAfterEvent $event): void
    {
        $product = $event->getProduct();
        $configs = $this->configProvider->getIds(ClassUtils::getClass($product));
        if (!$configs) {
            return;
        }

        foreach ($configs as $config) {
            if (!FieldConfigHelper::isMultiField($config)) {
                continue;
            }

            $this->cloneFiles($product, $config->getFieldName());
        }

        $this->managerRegistry->getManager()->persist($product);
        $this->managerRegistry->getManager()->flush();
    }

    private function cloneFiles(Product $product, string $fieldName): void
    {
        $files = new ArrayCollection([]);
        $sourceFiles = $product->get($fieldName);
        foreach ($sourceFiles as $sourceFileItem) {
            $file = $this->cloneFile($product, $fieldName, $sourceFileItem);
            $files->add($file);
        }

        if (!$files->isEmpty()) {
            $product->set($fieldName, $files);
            $product->set(sprintf('default_%s', $fieldName), $files->first());
        }
    }

    private function cloneFile(Product $targetProduct, string $fieldName, FileItem $sourceFileItem): FileItem
    {
        $fileItem = clone $sourceFileItem;
        $fileItem->setFile(clone $sourceFileItem->getFile());
        $fileItem->setSortOrder($sourceFileItem->getSortOrder());
        $fileItem->set(sprintf('product_%s', $fieldName), $targetProduct);

        return $fileItem;
    }
}
