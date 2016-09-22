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
class UPSTransportEntityListenerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->loadFixtures(['Oro\Bundle\UPSBundle\Tests\Functional\DataFixtures\LoadShippingRules']);
    }

    public function testPostUpdate()
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $shippingMethods = static::getContainer()
            ->get('oro_shipping.shipping_method.registry')
            ->getShippingMethods();
        /** @var Channel $ups_channel */
        $ups_channel = $this->getReference('ups:channel_1');
        /** @var UPSTransport $ups_transport */
        $ups_transport = $ups_channel->getTransport();
        $applShipServices = $ups_transport->getApplicableShippingServices();
        /** @var ShippingService $toBeDeletedService */
        $toBeDeletedService = $applShipServices->first();

        $configuredMethods = $em
            ->getRepository('OroShippingBundle:ShippingRuleMethodConfig')
            ->findBy([
                'method' => $this->getShippingMethodIdentifierByLabel($shippingMethods, $ups_channel->getName())]);
        $typesBefore = $em
            ->getRepository('OroShippingBundle:ShippingRuleMethodTypeConfig')
            ->findBy(['methodConfig' => $configuredMethods, 'type' => $toBeDeletedService->getCode()]);

        static::assertNotEmpty($typesBefore);

        $ups_transport->removeApplicableShippingService($toBeDeletedService);
        $em->persist($ups_transport);
        $em->flush();

        $typesAfter = $em
            ->getRepository('OroShippingBundle:ShippingRuleMethodTypeConfig')
            ->findBy(['methodConfig' => $configuredMethods, 'type' => $toBeDeletedService->getCode()]);

        static::assertEmpty($typesAfter);
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
