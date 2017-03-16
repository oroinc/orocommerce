<?php

namespace Oro\Bundle\CMSBundle\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CMSBundle\ContentBlock\ContentBlockResolver;
use Oro\Bundle\CMSBundle\ContentBlock\Model\ContentBlockView;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

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

    /** @var string */
    protected $scopeType;

    /**
     * @param ContentBlockResolver $contentBlockResolver
     * @param ManagerRegistry      $registry
     * @param ScopeManager         $scopeManager
     * @param string               $entityClass
     * @param string               $scopeType
     */
    public function __construct(
        ContentBlockResolver $contentBlockResolver,
        ManagerRegistry $registry,
        ScopeManager $scopeManager,
        $entityClass,
        $scopeType
    ) {
        $this->contentBlockResolver = $contentBlockResolver;
        $this->registry = $registry;
        $this->scopeManager = $scopeManager;
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
            return null;
        }

        return $this->contentBlockResolver->getContentBlockView($contentBlock, $context);
    }
}
