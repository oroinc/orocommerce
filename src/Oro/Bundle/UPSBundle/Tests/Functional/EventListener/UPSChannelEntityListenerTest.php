<?php

namespace Oro\Bundle\UPSBundle\Tests\Functional\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethod;

/**
 * @dbIsolation
 */
class UPSChannelEntityListenerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->loadFixtures([
            'Oro\Bundle\UPSBundle\Tests\Functional\DataFixtures\LoadShippingMethodsConfigsRules',
        ]);
    }

    public function testPreRemove()
    {
        /** @var ObjectManager $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        /** @var Channel $ups_channel */
        $ups_channel1 = $this->getReference('ups:channel_1');
        $ups_channel2 = $this->getReference('ups:channel_2');

        $configuredMethodsBefore = $em
            ->getRepository('OroShippingBundle:ShippingMethodConfig')
            ->findBy([
                'method' => UPSShippingMethod::IDENTIFIER . '_' . $ups_channel1->getId()]);
        static::assertNotEmpty($configuredMethodsBefore);

        $em->remove($ups_channel1);
        $em->flush();

        $configuredMethodsAfter = $em
            ->getRepository('OroShippingBundle:ShippingMethodConfig')
            ->findBy([
                'method' => UPSShippingMethod::IDENTIFIER . '_' . $ups_channel1->getId()]);
        static::assertEmpty($configuredMethodsAfter);

        $rulesWithoutShippingMethodsBefore = $em->getRepository('OroShippingBundle:ShippingMethodsConfigsRule')
            ->getRulesWithoutShippingMethods();
        static::assertEmpty($rulesWithoutShippingMethodsBefore);

        $em->remove($ups_channel2);
        $em->flush();

        $rulesWithoutShippingMethodsAfter = $em->getRepository('OroShippingBundle:ShippingMethodsConfigsRule')
            ->getRulesWithoutShippingMethods();
        static::assertNotEmpty($rulesWithoutShippingMethodsAfter);

        $enabledRulesWithoutShippingMethodsAfter = $em->getRepository('OroShippingBundle:ShippingMethodsConfigsRule')
            ->getRulesWithoutShippingMethods(true);
        static::assertEmpty($enabledRulesWithoutShippingMethodsAfter);
    }
}
