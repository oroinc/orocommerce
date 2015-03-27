<?php

namespace OroB2B\Bundle\CMSBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;

use OroB2B\Bundle\CMSBundle\Entity\Page;

class PageSlugListener
{
    /**
     * @param LifecycleEventArgs $event
     */
    public function postPersist(LifecycleEventArgs $event)
    {
        $this->process($event);
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function postUpdate(LifecycleEventArgs $event)
    {
        $this->process($event);
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function preRemove(LifecycleEventArgs $event)
    {
        $this->processRemoveSlugs($event);
    }

    /**
     * @param LifecycleEventArgs $event
     */
    protected function process(LifecycleEventArgs $event)
    {
        /** @var Page $page */
        $page = $event->getEntity();
        if (!$page instanceof Page) {
            return;
        }

        $expectedRoute = 'orob2b_cms_page_view';
        $expectedParameters = ['id' => $page->getId()];

        foreach ($page->getSlugs() as $slug) {
            $actualRoute = $slug->getRouteName();
            $actualParameters = $slug->getRouteParameters();

            if ($actualRoute !== $expectedRoute) {
                $slug->setRouteName($expectedRoute);
            }

            if ($actualParameters !== $expectedParameters) {
                $slug->setRouteParameters($expectedParameters);
            }
        }
    }

    /**
     * @param LifecycleEventArgs $event
     */
    protected function processRemoveSlugs(LifecycleEventArgs $event)
    {
        /** @var Page $page */
        $page = $event->getEntity();
        if (!$page instanceof Page) {
            return;
        }

        /** @var EntityManager $em */
        $em = $event->getEntityManager();

        $this->removePageSlugs($em, $page);
    }

    /**
     * @param  EntityManager $em
     * @param  Page $page
     */
    protected function removePageSlugs(EntityManager $em, Page $page)
    {
        foreach ($page->getSlugs() as $slug) {
            $page->removeSlug($slug);
            $em->remove($slug);
        }

        $children = $page->getChildPages();

        foreach ($children as $childPage) {
            $this->removePageSlugs($em, $childPage);
        }
    }
}
