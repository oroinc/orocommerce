<?php

namespace Oro\Bundle\FlatRateBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\FlatRateBundle\Entity\FlatRateSettings;
use Oro\Bundle\FlatRateBundle\Integration\FlatRateChannelType;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadFlatRateIntegration extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

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
    public function getDependencies()
    {
        return [
            'Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        if (!$this->container) {
            return;
        }

        if (!$this->container->hasParameter('oro_integration.entity.class')) {
            return;
        }

        $label = (new LocalizedFallbackValue())->setString('Flat Rate');

        $transport = new FlatRateSettings();
        $transport->addLabel($label);

        $channel = new Channel();
        $channel->setType(FlatRateChannelType::TYPE)
            ->setName('Flat Rate')
            ->setEnabled(true)
            ->setOrganization($this->getOrganization($manager))
            ->setTransport($transport);

        $manager->persist($channel);
        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     *
     * @return Organization|object
     */
    private function getOrganization(ObjectManager $manager)
    {
        if ($this->hasReference(LoadOrganizationAndBusinessUnitData::REFERENCE_DEFAULT_ORGANIZATION)) {
            return $this->getReference(LoadOrganizationAndBusinessUnitData::REFERENCE_DEFAULT_ORGANIZATION);
        } else {
            return $manager
                ->getRepository('OroOrganizationBundle:Organization')
                ->getFirst();
        }
    }
}
