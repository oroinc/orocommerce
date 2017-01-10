<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowAclExtension;
use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowMaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;

/**
 * This fixture adds root permissions to all existing frontend roles.
 */
class LoadWorkflowAcl extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCustomerUserRoles::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var AclManager $manager */
        $securityManager = $this->container->get('oro_security.acl.manager');

        if (!$securityManager->isAclEnabled()) {
            return;
        }

        $roles = $this->getRoles($manager);
        $this->addIsAuthenticatedAnonymouslyRole($roles);
        $rootOid = $securityManager->getRootOid(WorkflowAclExtension::NAME);
        foreach ($roles as $role) {
            $sid = $securityManager->getSid($role);
            $securityManager->setPermission($sid, $rootOid, WorkflowMaskBuilder::GROUP_SYSTEM, true);
        }

        $securityManager->flush();
    }

    /**
     * @param ObjectManager $manager
     *
     * @return CustomerUserRole[]
     */
    protected function getRoles(ObjectManager $manager)
    {
        return $manager->getRepository('OroCustomerBundle:CustomerUserRole')->findAll();
    }

    /**
     * Add inbuilt IS_AUTHENTICATED_ANONYMOUSLY to the roles list.
     * It should gain root permission along with frontend roles, because of it assigned to any user by Symfony,
     * and this behaviour, availability of the root permission for any user, expected by the system.
     *
     * @param $roles
     */
    private function addIsAuthenticatedAnonymouslyRole(&$roles)
    {
        $roles[] = 'IS_AUTHENTICATED_ANONYMOUSLY';
    }
}
