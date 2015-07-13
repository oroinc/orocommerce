<?php

namespace OroB2B\Bundle\ProductBundle\Duplicator;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;

class ProductDuplicator
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var SkuIncrementorInterface
     */
    protected $skuIncrementor;

    /**
     * @var AttachmentManager
     */
    protected $attachmentManager;

    /**
     * @var AttachmentProvider
     */
    protected $attachmentProvider;

    /**
     * @param ObjectManager $objectManager
     * @param EventDispatcherInterface $eventDispatcher
     * @param SkuIncrementorInterface $skuIncrementor
     * @param AttachmentManager $attachmentManager
     * @param AttachmentProvider $attachmentProvider
     */
    public function __construct(
        ObjectManager $objectManager,
        EventDispatcherInterface $eventDispatcher,
        SkuIncrementorInterface $skuIncrementor,
        AttachmentManager $attachmentManager,
        AttachmentProvider $attachmentProvider
    ) {
        $this->objectManager = $objectManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->skuIncrementor = $skuIncrementor;
        $this->attachmentManager = $attachmentManager;
        $this->attachmentProvider = $attachmentProvider;
    }

    /**
     * @param Product $product
     * @return Product
     */
    public function duplicate(Product $product)
    {
        $productCopy = $this->createProductCopy($product);

        $this->objectManager->persist($productCopy);
        $this->objectManager->flush();

        $this->eventDispatcher->dispatch(
            ProductDuplicateAfterEvent::NAME,
            new ProductDuplicateAfterEvent($productCopy, $product)
        );

        return $productCopy;
    }

    /**
     * @param Product $product
     * @return Product
     */
    private function createProductCopy(Product $product)
    {
        $productCopy = clone $product;

        $productCopy->setSku($this->skuIncrementor->increment($product->getSku()));
        $productCopy->setStatus($this->getDisabledStatus());

        $this->cloneChildObjects($product, $productCopy);

        return $productCopy;
    }

    /**
     * @param Product $product
     * @param Product $productCopy
     */
    private function cloneChildObjects(Product $product, Product $productCopy)
    {
        foreach ($product->getUnitPrecisions() as $unitPrecision) {
            $productCopy->addUnitPrecision(clone $unitPrecision);
        }

        if ($imageFile = $product->getImage()) {
            $imageFileCopy = $this->attachmentManager->copyAttachmentFile($imageFile);
            $productCopy->setImage($imageFileCopy);
        }

        $attachments = $this->attachmentProvider->getEntityAttachments($product);

        foreach ($attachments as $attachment) {
            $attachmentCopy = clone $attachment;
            $attachmentFileCopy = $this->attachmentManager->copyAttachmentFile($attachment->getFile());
            $attachmentCopy->setFile($attachmentFileCopy);

            $attachmentCopy->setTarget($productCopy);

            $this->objectManager->persist($attachmentCopy);
        }
    }

    /**
     * @return AbstractEnumValue
     */
    private function getDisabledStatus()
    {
        $className = ExtendHelper::buildEnumValueClassName('prod_status');

        return $this->objectManager->getRepository($className)->find(Product::STATUS_DISABLED);
    }
}
