<?php

namespace Oro\Bundle\UPSBundle\Tests\Functional\EventListener;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;

/**
 * @dbIsolation
 */
class UPSChannelEntityListenerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->loadFixtures(['Oro\Bundle\UPSBundle\Tests\Functional\DataFixtures\LoadShippingRules']);
    }

    public function testPreRemove()
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $shippingMethods = static::getContainer()
            ->get('oro_shipping.shipping_method.registry')
            ->getShippingMethods();
        /** @var Channel $ups_channel */
        $ups_channel = $this->getReference('ups:channel_1');

        $configuredMethodsBefore = $em
            ->getRepository('OroShippingBundle:ShippingRuleMethodConfig')
            ->findBy([
                'method' => $this->getShippingMethodIdentifierByLabel($shippingMethods, $ups_channel->getName())]);

        static::assertNotEmpty($configuredMethodsBefore);

        $em->remove($ups_channel);
        $em->flush();

        $configuredMethodsAfter = $em
            ->getRepository('OroShippingBundle:ShippingRuleMethodConfig')
            ->findBy([
                'method' => $this->getShippingMethodIdentifierByLabel($shippingMethods, $ups_channel->getName())]);

        static::assertEmpty($configuredMethodsAfter);
    }

    /**
     * @param array $shippingMethods
     * @param string $label
     * @return string|null
     */
    protected function getShippingMethodIdentifierByLabel($shippingMethods, $label)
    {
        foreach ($shippingMethods as $shippingMethod) {
            if ($shippingMethod->getLabel() === $label) {
                return $shippingMethod->getIdentifier();
            }
        }
        return null;
    }
}
