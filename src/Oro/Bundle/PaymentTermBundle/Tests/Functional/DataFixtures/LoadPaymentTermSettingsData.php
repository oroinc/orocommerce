<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;

class LoadPaymentTermSettingsData extends AbstractFixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $entity = new PaymentTermSettings();
            $entity->addLabel($this->createLocalizedFallbackValue('Payment Term ' . $i));
            $entity->addShortLabel($this->createLocalizedFallbackValue('Payment Term ' . $i));
            $manager->persist($entity);
            $this->setReference('payment_term:transport_' . $i, $entity);
        }
        $manager->flush();
    }

    private function createLocalizedFallbackValue(string $string): LocalizedFallbackValue
    {
        $label = new LocalizedFallbackValue();
        $label->setString($string);

        return $label;
    }
}
