<?php

namespace Oro\Bundle\PayPalBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOtherOrganizations;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadPayPalChannelData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    public const PAYPAL_PAYFLOW_GATAWAY1 = 'paypal:channel_1';
    public const PAYPAL_PAYFLOW_GATAWAY2 = 'paypal:channel_2';
    public const PAYPAL_PAYFLOW_GATAWAY3 = 'paypal:channel_5';
    public const PAYPAL_PAYMENTS_PRO1 = 'paypal:channel_3';
    public const PAYPAL_PAYMENTS_PRO2 = 'paypal:channel_4';
    public const PAYPAL_PAYMENTS_PRO3 = 'paypal:channel_6';

    /**
     * @var array Channels configuration
     */
    private $channelData = [
        self::PAYPAL_PAYFLOW_GATAWAY1 => [
            'name' => 'PayPal1',
            'type' => 'paypal_payflow_gateway',
            'enabled' => true,
            'organization' => LoadOrganization::ORGANIZATION,
        ],
        self::PAYPAL_PAYFLOW_GATAWAY2 => [
            'name' => 'PayPal2',
            'type' => 'paypal_payflow_gateway',
            'enabled' => false,
            'organization' => LoadOrganization::ORGANIZATION,
        ],
        self::PAYPAL_PAYFLOW_GATAWAY3 => [
            'name' => 'PayPal5',
            'type' => 'paypal_payflow_gateway',
            'enabled' => true,
            'organization' => LoadOtherOrganizations::ORGANIZATION_1,
        ],
        self::PAYPAL_PAYMENTS_PRO1 => [
            'name' => 'PayPal3',
            'type' => 'paypal_payments_pro',
            'enabled' => true,
            'organization' => LoadOrganization::ORGANIZATION,
        ],
        self::PAYPAL_PAYMENTS_PRO2 => [
            'name' => 'PayPal4',
            'type' => 'paypal_payments_pro',
            'enabled' => true,
            'organization' => LoadOrganization::ORGANIZATION,
        ],
        self::PAYPAL_PAYMENTS_PRO3 => [
            'name' => 'PayPal6',
            'type' => 'paypal_payments_pro',
            'enabled' => true,
            'organization' => LoadOtherOrganizations::ORGANIZATION_1,
        ],
    ];

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
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadOrganization::class, LoadUser::class, LoadOtherOrganizations::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');
        $admin = $userManager->findUserByEmail(LoadAdminUserData::DEFAULT_ADMIN_EMAIL);

        foreach ($this->channelData as $reference => $data) {
            $entity = new Channel();
            $entity->setName($data['name']);
            $entity->setType($data['type']);
            $entity->setEnabled($data['enabled']);
            $entity->setDefaultUserOwner($admin);
            $entity->setOrganization($this->getReference($data['organization']));
            $entity->setTransport(new PayPalSettings());
            $this->setReference($reference, $entity);

            $manager->persist($entity);
        }
        $manager->flush();
    }
}
