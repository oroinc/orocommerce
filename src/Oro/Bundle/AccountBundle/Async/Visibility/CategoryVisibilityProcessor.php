<?php

namespace Oro\Bundle\AccountBundle\Async\Visibility;

use Oro\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\AccountBundle\Model\VisibilityMessageFactory;
use Oro\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Bundle\ProductBundle\Model\ProductMessageHandler;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CategoryVisibilityProcessor extends AbstractVisibilityProcessor
{

    /**
     * @var ProductMessageHandler
     */
    protected $productMessageHandler;

    /**
     * @param RegistryInterface $registry
     * @param VisibilityMessageFactory $messageFactory
     * @param LoggerInterface $logger
     * @param CacheBuilderInterface $cacheBuilder
     * @param ProductMessageHandler $productMessageHandler
     */
    public function __construct(
        RegistryInterface $registry,
        VisibilityMessageFactory $messageFactory,
        LoggerInterface $logger,
        CacheBuilderInterface $cacheBuilder,
        ProductMessageHandler $productMessageHandler
    ) {
        parent::__construct($registry, $messageFactory, $logger, $cacheBuilder);
        $this->productMessageHandler = $productMessageHandler;
    }

    /**
     * @param object|VisibilityInterface $entity
     */
    protected function resolveVisibilityByEntity($entity)
    {
        $this->cacheBuilder->resolveVisibilitySettings($entity);
        if ($entity instanceof CategoryVisibility) {
            /** @var $entity CategoryVisibility */
            foreach ($entity->getCategory()->getProducts() as $product) {
                if ($product->getId() != 1) {
                    continue;
                }
                $this->productMessageHandler->addProductMessageToSchedule(
                    'oro_account.visibility.change_product_category',
                    $product
                );
            }
            $this->productMessageHandler->sendScheduledMessages();
        }
    }
}
