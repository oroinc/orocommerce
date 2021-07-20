<?php

namespace Oro\Bundle\CMSBundle\Layout\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\ContentBlock\ContentBlockResolver;
use Oro\Bundle\CMSBundle\ContentBlock\Model\ContentBlockView;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Psr\Log\LoggerInterface;

/**
 * Layout data provider for Content Blocks.
 * Add possibility to get appropriate `Oro\Bundle\CMSBundle\ContentBlock\Model\ContentBlockView`
 * according Content Block alias.
 */
class ContentBlockDataProvider
{
    /** @var ContentBlockResolver */
    protected $contentBlockResolver;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $entityClass;

    /** @var ScopeManager */
    protected $scopeManager;

    /** @var LoggerInterface */
    protected $logger;

    /** @var string */
    protected $scopeType;

    /**
     * @param ContentBlockResolver $contentBlockResolver
     * @param ManagerRegistry      $registry
     * @param ScopeManager         $scopeManager
     * @param LoggerInterface      $logger
     * @param string               $entityClass
     * @param string               $scopeType
     */
    public function __construct(
        ContentBlockResolver $contentBlockResolver,
        ManagerRegistry $registry,
        ScopeManager $scopeManager,
        LoggerInterface $logger,
        $entityClass,
        $scopeType
    ) {
        $this->contentBlockResolver = $contentBlockResolver;
        $this->registry = $registry;
        $this->scopeManager = $scopeManager;
        $this->logger = $logger;
        $this->entityClass = $entityClass;
        $this->scopeType = $scopeType;
    }

    public function getContentBlockView(string $alias): ?ContentBlockView
    {
        $criteria = $this->scopeManager->getCriteria($this->scopeType);
        $contentBlock = $this->getContentBlock($alias);

        if (null === $contentBlock) {
            $this->logger->notice('Content block with alias "{alias}" doesn\'t exists', ['alias' => $alias]);

            return null;
        }

        return $this->contentBlockResolver->getContentBlockViewByCriteria($contentBlock, $criteria);
    }

    private function getContentBlock(string $alias): ?ContentBlock
    {
        $repo = $this->registry->getManagerForClass($this->entityClass)->getRepository($this->entityClass);
        /** @var ContentBlock $contentBlock */
        $contentBlock = $repo->findOneBy(['alias' => $alias]);

        return $contentBlock;
    }
}
