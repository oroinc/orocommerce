<?php

namespace OroB2B\Bundle\MenuBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use OroB2B\Bundle\MenuBundle\Menu\DatabaseMenuProvider;

class LocalizedFallbackValueListener
{
    const MENU_ITEM_CLASS = 'OroB2B\Bundle\MenuBundle\Entity\MenuItem';

    /**
     * @var DatabaseMenuProvider
     */
    protected $menuProvider;

    /**
     * @param DatabaseMenuProvider $menuProvider
     */
    public function __construct(DatabaseMenuProvider $menuProvider)
    {
        $this->menuProvider = $menuProvider;
    }

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $uow = $event->getEntityManager()->getUnitOfWork();

        $fallbackValues = $this->getFallbackValues($uow);
        if (count($fallbackValues) === 0) {
            return;
        }

        $menuItems = $this->getMenuItems($uow);
        if (count($menuItems) === 0) {
            return;
        }

        $rootIds = [];
        /** @var MenuItem $menuItem */
        foreach ($menuItems as $menuItem) {
            $titles = $menuItem->getTitles()->toArray();
            if (count(array_intersect($titles, $fallbackValues)) > 0 &&
                !in_array($menuItem->getRoot(), $rootIds, true)
            ) {
                $rootIds[] = $menuItem->getRoot();
            }
        }

        if (count($rootIds) === 0) {
            return;
        }

        $roots = $event->getEntityManager()->getRepository(self::MENU_ITEM_CLASS)->findBy(['id' => $rootIds]);
        foreach ($roots as $root) {
            $alias = $root->getDefaultTitle()->getString();
            $this->menuProvider->rebuildCacheByAlias($alias);
        }
    }

    /**
     * @param UnitOfWork $uow
     * @return array
     */
    protected function getMenuItems(
        UnitOfWork $uow
    ) {
        $map = $uow->getIdentityMap();

        return array_key_exists(self::MENU_ITEM_CLASS, $map) ? $map[self::MENU_ITEM_CLASS] : [];
    }

    /**
     * @param UnitOfWork $uow
     * @return array
     */
    protected function getFallbackValues(
        UnitOfWork $uow
    ) {
        $fallbackValues = [];
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof LocalizedFallbackValue) {
                $fallbackValues[] = $entity;
            }
        }

        return $fallbackValues;
    }
}
