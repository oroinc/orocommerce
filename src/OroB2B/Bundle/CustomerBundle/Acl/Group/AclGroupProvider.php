<?php

namespace OroB2B\Bundle\CustomerBundle\Acl\Group;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;

class AclGroupProvider implements AclGroupProviderInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

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
        return $this->container->get('oro_security.security_facade');
    }
}
