<?php

namespace Oro\Bundle\CMSBundle\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CMSBundle\ContentBlock\ContentBlockResolver;
use Oro\Bundle\CMSBundle\ContentBlock\Model\ContentBlockView;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Psr\Log\LoggerInterface;

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

    /**
     * @param string $alias
     *
     * @return ContentBlockView|null
     */
    public function getContentBlockView($alias)
    {
        $criteria = $this->scopeManager->getCriteria($this->scopeType);
        $context = $criteria->toArray();
        $repo = $this->registry->getManagerForClass($this->entityClass)->getRepository($this->entityClass);
        /** @var ContentBlock $contentBlock */
        $contentBlock = $repo->findOneBy(['alias' => $alias]);
        if (null === $contentBlock) {
            $this->logger->notice('Content block with alias "{alias}" doesn\'t exists', ['alias' => $alias]);

            return null;
        }

        return $this->contentBlockResolver->getContentBlockView($contentBlock, $context);
    }
}
