<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\Frontend\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\FlatRateShippingBundle\Entity\FlatRateSettings;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Loads shipping methods for test orders.
 */
class LoadShippingMethods extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            '@OroOrderBundle/Tests/Functional/Api/Frontend/DataFixtures/orders.yml'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $transportLabel = new LocalizedFallbackValue();
        $transportLabel->setString('Flat Rate');
        $manager->persist($transportLabel);

        $transport = new FlatRateSettings();
        $transport->addLabel($transportLabel);
        $manager->persist($transport);

        $channel = new Channel();
        $channel->setName('Flat Rate Channel');
        $channel->setType('flat_rate');
        $channel->setTransport($transport);
        $channel->setOrganization($this->getReference('organization'));
        $channel->setDefaultUserOwner($this->getReference('user'));
        $manager->persist($channel);

        $this->setReference('flat_rate_shipping_channel', $channel);

        $manager->flush();

        /** @var Order $order1 */
        $order1 = $this->getReference('order1');
        $order1->setShippingMethod('flat_rate_' . $channel->getId());
        $order1->setShippingMethodType('primary');
        $manager->flush();
    }
}
