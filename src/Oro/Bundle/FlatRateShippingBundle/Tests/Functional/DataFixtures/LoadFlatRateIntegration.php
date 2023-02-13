<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FlatRateShippingBundle\Entity\FlatRateSettings;
use Oro\Bundle\FlatRateShippingBundle\Integration\FlatRateChannelType;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class LoadFlatRateIntegration extends AbstractFixture
{
    public const REFERENCE_FLAT_RATE = 'flat_rate_integration';

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $label = new LocalizedFallbackValue();
        $label->setString('Flat Rate');

        $transport = new FlatRateSettings();
        $transport->addLabel($label);

        $channel = new Channel();
        $channel->setType(FlatRateChannelType::TYPE);
        $channel->setName('Flat Rate');
        $channel->setEnabled(true);
        $channel->setTransport($transport);
        $channel->setOrganization($manager->getRepository(Organization::class)->getFirst());

        $manager->persist($channel);
        $manager->flush();

        $this->setReference(self::REFERENCE_FLAT_RATE, $channel);
    }
}
