<?php

namespace Oro\Bundle\MenuBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MenuBundle\Entity\MenuItem;
use Oro\Bundle\MenuBundle\Entity\Repository\MenuItemRepository;
use Oro\Bundle\MenuBundle\Menu\DatabaseMenuProvider;

class CacheWarmer implements CacheWarmerInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var DatabaseMenuProvider
     */
    protected $menuProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param DatabaseMenuProvider $menuProvider
     */
    public function __construct(DoctrineHelper $doctrineHelper, DatabaseMenuProvider $menuProvider)
    {
        $this->menuProvider = $menuProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param string $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * {@inheritDoc}
     */
    public function warmUp($cacheDir)
    {
        foreach ($this->getMenus() as $menuItem) {
            $alias = $menuItem->getDefaultTitle()->getString();
            $this->menuProvider->rebuildCacheByAlias($alias);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isOptional()
    {
        return true;
    }

    /**
     * @return MenuItem[]
     */
    protected function getMenus()
    {
        /** @var MenuItemRepository $repo */
        $repo = $this->doctrineHelper->getEntityRepository($this->entityClass);

        return $repo->findRoots();
    }
}
