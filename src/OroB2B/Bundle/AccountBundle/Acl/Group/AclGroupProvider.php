<?php

namespace OroB2B\Bundle\AccountBundle\Acl\Group;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class AclGroupProvider implements AclGroupProviderInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

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

    /** {@inheritdoc} */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
