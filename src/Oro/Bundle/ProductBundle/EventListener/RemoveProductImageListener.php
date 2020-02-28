<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\Event\OnClearEventArgs;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\MessageProcessor\ImageRemoveMessageProcessor;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Removes product image files and directories when removing product images
 */
class RemoveProductImageListener
{
    /**
     * @var int
     */
    private $batchSize = 100;

    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    /**
     * @var array|File[]
     */
    private $imagesToRemove = [];

    /**
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(
        MessageProducerInterface $messageProducer
    ) {
        $this->messageProducer = $messageProducer;
    }

    /**
     * @param int $batchSize
     */
    public function setBatchSize(int $batchSize)
    {
        if ($batchSize > 0) {
            $this->batchSize = $batchSize;
        }
    }

    /**
     * @param ProductImage $productImage
     */
    public function preRemove(ProductImage $productImage)
    {
        $file = $productImage->getImage();
        if ($file) {
            $this->imagesToRemove[$file->getId()] = $file;
        }
    }

    public function postFlush()
    {
        if (!$this->imagesToRemove) {
            return;
        }

        $imagesBatch = [];
        $count = 0;
        foreach ($this->imagesToRemove as $id => $imageFile) {
            $imagesBatch[] = [
                'id' => $id,
                'fileName' => $imageFile->getFilename(),
                'originalFileName' => $imageFile->getOriginalFilename(),
                'parentEntityClass' => $imageFile->getParentEntityClass()
            ];
            $count++;

            if ($count === $this->batchSize) {
                $this->messageProducer->send(ImageRemoveMessageProcessor::IMAGE_REMOVE_TOPIC, $imagesBatch);
                $imagesBatch = [];
                $count = 0;
            }
        }

        if ($imagesBatch) {
            $this->messageProducer->send(ImageRemoveMessageProcessor::IMAGE_REMOVE_TOPIC, $imagesBatch);
        }
    }

    /**
     * @param OnClearEventArgs $event
     */
    public function onClear(OnClearEventArgs $event)
    {
        if (!$event->getEntityClass() || $event->getEntityClass() === ProductImage::class) {
            $this->imagesToRemove = [];
        }
    }
}
