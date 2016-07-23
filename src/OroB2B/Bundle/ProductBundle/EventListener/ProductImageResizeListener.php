<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ProductBundle\Command\ResizeProductImageCommand;
use OroB2B\Bundle\ProductBundle\Entity\ProductImage;
use OroB2B\Bundle\ProductBundle\Event\ProductImageResizeEvent;

class ProductImageResizeListener
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param ProductImageResizeEvent $event
     */
    public function resizeProductImage(ProductImageResizeEvent $event)
    {
        $manager = $this->doctrineHelper->getEntityManagerForClass(Job::class);
        $job = $this->createJob($event->getProductImage(), $event->getForceOption());
        $manager->persist($job);
        $manager->flush($job);
    }

    /**
     * @param ProductImage $productImage
     * @param bool $forceOption
     * @return Job
     */
    private function createJob(ProductImage $productImage, $forceOption)
    {
        $commandArgs = [$productImage->getId()];
        if ($forceOption) {
            $commandArgs[] = sprintf('--%s', ResizeProductImageCommand::OPTION_FORCE);
        }

        return new Job(ResizeProductImageCommand::COMMAND_NAME, $commandArgs);
    }
}
