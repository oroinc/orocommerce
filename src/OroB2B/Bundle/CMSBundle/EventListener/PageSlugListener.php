<?php

namespace OroB2B\Bundle\CMSBundle\EventListener;

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
            $changeSet = [];

            if ($actualRoute !== $expectedRoute) {
                $slug->setRouteName($expectedRoute);
                $changeSet['routeName'] = [$actualRoute, $expectedRoute];
            }

            if ($actualParameters !== $expectedParameters) {
                $slug->setRouteParameters($expectedParameters);
                $changeSet['routeParameters'] = [$actualParameters, $expectedParameters];
            }

            if ($changeSet) {
                $unitOfWork = $event->getEntityManager()->getUnitOfWork();
                $unitOfWork->scheduleExtraUpdate($slug, $changeSet);
            }
        }
    }
}
