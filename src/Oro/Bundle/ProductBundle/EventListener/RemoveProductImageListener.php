<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\Event\OnClearEventArgs;
use Oro\Bundle\AttachmentBundle\Async\Topic\AttachmentRemoveImageTopic;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Sends MQ messages to remove image files related to removed ProductImage entity.
 */
class RemoveProductImageListener
{
    /** @var int */
    private $batchSize = 100;

    /** @var MessageProducerInterface */
    private $messageProducer;

    /** @var File[] */
    private $imagesToRemove = [];

    public function __construct(MessageProducerInterface $messageProducer)
    {
        $this->messageProducer = $messageProducer;
    }

    public function setBatchSize(int $batchSize): void
    {
        if ($batchSize > 0) {
            $this->batchSize = $batchSize;
        }
    }

    public function preRemove(ProductImage $productImage): void
    {
        $file = $productImage->getImage();
        if ($file) {
            $this->imagesToRemove[$file->getId()] = $file;
        }
    }

    public function postFlush(): void
    {
        if (!$this->imagesToRemove) {
            return;
        }

        $imagesBatch = [];
        $count = 0;
        foreach ($this->imagesToRemove as $id => $imageFile) {
            if ($imageFile->getExternalUrl() !== null) {
                // Externally stored files cannot be managed.
                continue;
            }

            $imagesBatch[] = [
                'id' => $id,
                'fileName' => $imageFile->getFilename(),
                'originalFileName' => $imageFile->getOriginalFilename(),
                'parentEntityClass' => $imageFile->getParentEntityClass()
            ];
            $count++;

            if ($count === $this->batchSize) {
                $this->messageProducer->send(AttachmentRemoveImageTopic::getName(), ['images' => $imagesBatch]);
                $imagesBatch = [];
                $count = 0;
            }
        }

        if ($imagesBatch) {
            $this->messageProducer->send(AttachmentRemoveImageTopic::getName(), ['images' => $imagesBatch]);
        }
    }

    public function onClear(OnClearEventArgs $event): void
    {
        if (!$event->getEntityClass() || $event->getEntityClass() === ProductImage::class) {
            $this->imagesToRemove = [];
        }
    }
}
