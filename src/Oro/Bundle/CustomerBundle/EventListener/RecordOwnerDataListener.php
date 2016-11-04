<?php

namespace Oro\Bundle\CustomerBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Helper\CustomerUserHelper;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\Security\Core\SecurityContextInterface;

class RecordOwnerDataListener
{
    /** @var ServiceLink */
    protected $securityContextLink;

    /** @var CustomerUserHelper */
    protected $customerUserHelper;

    /**
     * @param ServiceLink    $securityContextLink
     * @param CustomerUserHelper $customerUserHelper
     */
    public function __construct(ServiceLink $securityContextLink, CustomerUserHelper $customerUserHelper)
    {
        $this->securityContextLink = $securityContextLink;
        $this->customerUserHelper  = $customerUserHelper;
    }

    /**
     * Handle prePersist.
     *
     * @param LifecycleEventArgs $args
     * @throws \LogicException when getOwner method isn't implemented for entity with ownership type
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $token = $this->getSecurityContext()->getToken();
        if (!$token) {
            return;
        }
        $user = $token->getUser();
        if (!($user instanceof AccountUser)) {
            return;
        }
        $entity = $args->getEntity();

        $this->customerUserHelper->setAccountUser($user, $entity, true);
    }

    /**
     * @return SecurityContextInterface
     */
    protected function getSecurityContext()
    {
        return $this->securityContextLink->getService();
    }
}
