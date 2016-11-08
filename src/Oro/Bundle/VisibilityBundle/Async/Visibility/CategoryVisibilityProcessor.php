<?php

namespace Oro\Bundle\VisibilityBundle\Async\Visibility;

use Oro\Bundle\EntityBundle\ORM\DatabaseExceptionHelper;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Model\MessageFactoryInterface;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Bundle\ProductBundle\Model\ProductMessageHandler;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class CategoryVisibilityProcessor extends AbstractVisibilityProcessor
{

    /**
     * @var ProductMessageHandler
     */
    protected $productMessageHandler;

    /**
     * @param ManagerRegistry $registry
     * @param MessageFactoryInterface $messageFactory
     * @param LoggerInterface $logger
     * @param CacheBuilderInterface $cacheBuilder
     * @param DatabaseExceptionHelper $databaseExceptionHelper
     * @param ProductMessageHandler $productMessageHandler
     */
    public function __construct(
        ManagerRegistry $registry,
        MessageFactoryInterface $messageFactory,
        LoggerInterface $logger,
        CacheBuilderInterface $cacheBuilder,
        DatabaseExceptionHelper $databaseExceptionHelper,
        ProductMessageHandler $productMessageHandler
    ) {
        parent::__construct($registry, $messageFactory, $logger, $cacheBuilder, $databaseExceptionHelper);
        $this->productMessageHandler = $productMessageHandler;
    }

    /**
     * @param object|CategoryVisibility $entity
     */
    protected function resolveVisibilityByEntity($entity)
    {
        $this->cacheBuilder->resolveVisibilitySettings($entity);
    }
}
