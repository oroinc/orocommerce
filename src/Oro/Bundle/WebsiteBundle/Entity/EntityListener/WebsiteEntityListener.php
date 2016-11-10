<?php

namespace Oro\Bundle\WebsiteBundle\Entity\EntityListener;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class WebsiteEntityListener
{
    /**
     * @param Website $website
     * @param LifecycleEventArgs $event
     */
    public function prePersist(Website $website, LifecycleEventArgs $event)
    {
        /** @var EntityManagerInterface $em */
        $em = $event->getObjectManager();
        $uow = $em->getUnitOfWork();

        $scope = new Scope();
        /** @noinspection PhpUndefinedMethodInspection - field added as entity extend */
        $scope->setWebsite($website);

        $uow->scheduleForInsert($scope);
    }
}
