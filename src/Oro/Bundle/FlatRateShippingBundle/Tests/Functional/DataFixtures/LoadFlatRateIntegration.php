<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\FlatRateShippingBundle\Entity\FlatRateSettings;
use Oro\Bundle\FlatRateShippingBundle\Integration\FlatRateChannelType;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

class LoadFlatRateIntegration extends AbstractFixture
{
    const REFERENCE_FLAT_RATE = 'flat_rate_integration';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $label = (new LocalizedFallbackValue())->setString('Flat Rate');

        $transport = new FlatRateSettings();
        $transport->addLabel($label);

        $channel = new Channel();
        $channel->setType(FlatRateChannelType::TYPE)
            ->setName('Flat Rate')
            ->setEnabled(true)
            ->setTransport($transport);

        $manager->persist($channel);
        $manager->flush();

        $this->setReference(self::REFERENCE_FLAT_RATE, $channel);
    }
}
