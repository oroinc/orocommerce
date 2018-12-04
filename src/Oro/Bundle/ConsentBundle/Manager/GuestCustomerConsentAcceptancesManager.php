<?php

namespace Oro\Bundle\ConsentBundle\Manager;

use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Storage\CustomerConsentAcceptancesStorageInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Psr\Log\LoggerInterface;

/**
 * Class that used in workflow to save consent acceptances from the storage to the database
 */
class GuestCustomerConsentAcceptancesManager
{
    /**
     * @var CustomerConsentAcceptancesStorageInterface
     */
    private $storage;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param LoggerInterface $logger
     */
    public function __construct(DoctrineHelper $doctrineHelper, LoggerInterface $logger)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->logger = $logger;
    }

    /**
     * @param CustomerConsentAcceptancesStorageInterface $storage
     */
    public function setStorage(CustomerConsentAcceptancesStorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param CustomerUser $customerUser
     */
    public function flushCustomerConsentAcceptancesFromStorage(CustomerUser $customerUser)
    {
        $acceptances = $this->storage->getData();
        if (empty($acceptances)) {
            return;
        }

        $em = $this->doctrineHelper->getEntityManager(ConsentAcceptance::class);
        try {
            /** @var ConsentAcceptance $acceptance */
            foreach ($acceptances as $acceptance) {
                $acceptance->setCustomerUser($customerUser);
                $em->persist($acceptance);
            }

            $em->flush();
            $this->storage->clearData();
        } catch (\Exception $e) {
            $this->logger->error(
                'Error occurred while saving ConsentAcceptances to the database.',
                ['exception' => $e]
            );
        }
    }
}
