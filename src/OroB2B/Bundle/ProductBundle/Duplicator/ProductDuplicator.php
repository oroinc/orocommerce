<?php

namespace OroB2B\Bundle\ProductBundle\Duplicator;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;

class ProductDuplicator
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

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
     * @param DoctrineHelper $doctrineHelper
     * @param EventDispatcherInterface $eventDispatcher
     * @param AttachmentManager $attachmentManager
     * @param AttachmentProvider $attachmentProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EventDispatcherInterface $eventDispatcher,
        AttachmentManager $attachmentManager,
        AttachmentProvider $attachmentProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->eventDispatcher = $eventDispatcher;
        $this->attachmentManager = $attachmentManager;
        $this->attachmentProvider = $attachmentProvider;
    }

    /**
     * @param Product $product
     * @return Product
     * @throws \Exception
     */
    public function duplicate(Product $product)
    {
        $objectManager = $this->doctrineHelper->getEntityManager($product);
        $objectManager->getConnection()->beginTransaction();

        try {
            $productCopy = $this->createProductCopy($product);

            $objectManager->persist($productCopy);
            $objectManager->flush();

            $this->eventDispatcher->dispatch(
                ProductDuplicateAfterEvent::NAME,
                new ProductDuplicateAfterEvent($productCopy, $product)
            );

            $objectManager->getConnection()->commit();
        } catch (\Exception $e) {
            $objectManager->getConnection()->rollBack();
            throw $e;
        }

        return $productCopy;
    }

    /**
     * @param SkuIncrementorInterface $skuIncrementor
     */
    public function setSkuIncrementor(SkuIncrementorInterface $skuIncrementor)
    {
        $this->skuIncrementor = $skuIncrementor;
    }

    /**
     * @param Product $product
     * @return Product
     */
    protected function createProductCopy(Product $product)
    {
        $productCopy = clone $product;

        $productCopy->setSku($this->skuIncrementor->increment($product->getSku()));
        $productCopy->setStatus(Product::STATUS_DISABLED);

        $this->cloneChildObjects($product, $productCopy);

        return $productCopy;
    }

    /**
     * @param Product $product
     * @param Product $productCopy
     */
    protected function cloneChildObjects(Product $product, Product $productCopy)
    {
        $primaryPrecision = $product->getPrimaryUnitPrecision();
        if ($primaryPrecision) {
            $productCopy->setPrimaryUnitPrecision(clone $primaryPrecision);
        }

        foreach ($product->getAdditionalUnitPrecisions() as $unitPrecision) {
            $productCopy->addAdditionalUnitPrecision(clone $unitPrecision);
        }

        foreach ($product->getNames() as $name) {
            $productCopy->addName(clone $name);
        }

        foreach ($product->getDescriptions() as $description) {
            $productCopy->addDescription(clone $description);
        }

        foreach ($product->getShortDescriptions() as $shortDescription) {
            $productCopy->addShortDescription(clone $shortDescription);
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

            $this->doctrineHelper->getEntityManager($attachmentCopy)->persist($attachmentCopy);
        }
    }
}
