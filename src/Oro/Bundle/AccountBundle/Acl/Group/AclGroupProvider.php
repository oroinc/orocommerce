<?php

namespace Oro\Bundle\AccountBundle\Acl\Group;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\AccountBundle\Entity\AccountUser;

class AclGroupProvider implements AclGroupProviderInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function supports()
    {
        $user = $this->getSecurityFacade()->getLoggedUser();

        return !is_object($user) || $user instanceof AccountUser;
    }

    /**
     * {@inheritDoc}
     */
    public function getGroup()
    {
        return AccountUser::SECURITY_GROUP;
    }

    /**
     * @return SecurityFacade
     */
    protected function getSecurityFacade()
    {
        if (!$this->container) {
            throw new \InvalidArgumentException('ContainerInterface not injected');
        }

        return $this->container->get('oro_security.security_facade');
    }
}
