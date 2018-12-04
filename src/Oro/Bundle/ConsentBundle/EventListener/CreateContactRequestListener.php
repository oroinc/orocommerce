<?php

namespace Oro\Bundle\ConsentBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Helper\ContactRequestHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

/**
 * Event listener that creates contact request after customer decides to uncheck
 * his acceptance of consent
 */
class CreateContactRequestListener
{
    /**
     * @var ContactRequestHelper
     */
    private $contactRequestHelper;

    /**
     * @var FeatureChecker
     */
    private $featureChecker;

    /**
     * @var ConsentAcceptance[]
     */
    private $acceptancesToNotifyOnDelete = [];

    /**
     * @param ContactRequestHelper $contactRequestHelper
     * @param FeatureChecker $featureChecker
     */
    public function __construct(
        ContactRequestHelper $contactRequestHelper,
        FeatureChecker $featureChecker
    ) {
        $this->contactRequestHelper = $contactRequestHelper;
        $this->featureChecker = $featureChecker;
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        if ($this->featureChecker->isFeatureEnabled('consents')) {
            $uow = $args->getEntityManager()->getUnitOfWork();

            foreach ($uow->getScheduledEntityDeletions() as $oid => $entity) {
                if ($entity instanceof ConsentAcceptance
                    && $entity->getConsent()->getDeclinedNotification()
                ) {
                    $this->acceptancesToNotifyOnDelete[] = $entity;
                }
            }
        }
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if ($this->acceptancesToNotifyOnDelete) {
            while ($this->acceptancesToNotifyOnDelete) {
                $acceptance = array_shift($this->acceptancesToNotifyOnDelete);
                $contactRequest = $this->contactRequestHelper->createContactRequest($acceptance);
                $args->getEntityManager()->persist($contactRequest);
            }

            $args->getEntityManager()->flush();
        }
    }
}
