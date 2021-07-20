<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\ConfigBundle\Entity\Repository\ConfigValueRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\PaymentBundle\Method\Event\MethodRenamingEventDispatcherInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractMoveConfigValuesToSettings extends AbstractFixture implements ContainerAwareInterface
{
    const SECTION_NAME = '';

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var bool
     */
    protected $installed;

    /**
     * @var MethodRenamingEventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->doctrine = $container->get('doctrine');
        $this->installed = $container->hasParameter('installed') && $container->getParameter('installed');
        $this->dispatcher = $container->get('oro_payment.method.event.dispatcher.method_renaming');
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        if ($this->installed) {
            $this->moveConfigFromSystemConfigToIntegration($manager, $this->getOrganization());
            if ('' !== static::SECTION_NAME) {
                $this->getConfigValueRepository()->removeBySection(static::SECTION_NAME);
            }
        }
    }

    abstract protected function moveConfigFromSystemConfigToIntegration(
        ObjectManager $manager,
        OrganizationInterface $organization
    );

    /**
     * @return ConfigValueRepository
     */
    protected function getConfigValueRepository()
    {
        return $this->doctrine->getManagerForClass(ConfigValue::class)->getRepository(ConfigValue::class);
    }

    /**
     * @return Organization
     */
    protected function getOrganization()
    {
        return $this->doctrine->getRepository(Organization::class)->getFirst();
    }
}
