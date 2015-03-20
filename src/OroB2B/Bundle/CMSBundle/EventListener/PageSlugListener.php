<?php

namespace OroB2B\Bundle\CMSBundle\EventListener;

use Doctrine\DBAL\Types\Type;
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

        $unitOfWork = $event->getEntityManager()->getUnitOfWork();
        $connection = $event->getEntityManager()->getConnection();

        $expectedRoute = 'orob2b_cms_page_view';
        $expectedParameters = ['id' => $page->getId()];

        foreach ($page->getSlugs() as $slug) {
            $actualRoute = $slug->getRouteName();
            $actualParameters = $slug->getRouteParameters();
            if ($actualRoute !== $expectedRoute) {
                $unitOfWork->scheduleExtraUpdate($slug, ['routeName' => [$actualRoute, $expectedRoute]]);
            }
            if ($actualParameters !== $expectedParameters) {
                $unitOfWork->scheduleExtraUpdate(
                    $slug,
                    [
                        'routeParameters' => [
                            $connection->convertToDatabaseValue($actualParameters, Type::TARRAY),
                            $connection->convertToDatabaseValue($expectedParameters, Type::TARRAY),
                        ]
                    ]
                );
            }
        }
    }
}
