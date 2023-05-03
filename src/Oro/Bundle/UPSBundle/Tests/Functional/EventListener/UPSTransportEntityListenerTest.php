<?php

namespace Oro\Bundle\UPSBundle\Tests\Functional\EventListener;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Tests\Functional\DataFixtures\LoadShippingMethodsConfigsRules;

class UPSTransportEntityListenerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadUser::class]);
        $this->setUpTokenStorage();
        $this->loadFixtures([LoadShippingMethodsConfigsRules::class]);
    }

    private function setUpTokenStorage(): void
    {
        $user = $this->getReference(LoadUser::USER);
        $token = new UsernamePasswordOrganizationToken(
            $user,
            'password',
            'main',
            $user->getOrganization(),
            $user->getUserRoles()
        );
        self::getContainer()->get('security.token_storage')->setToken($token);
    }

    public function testPostUpdate()
    {
        $em = self::getContainer()->get('doctrine')->getManager();
        /** @var Channel $ups_channel */
        $ups_channel = $this->getReference('ups:channel_1');
        /** @var UPSTransport $ups_transport */
        $ups_transport = $ups_channel->getTransport();
        $applShipServices = $ups_transport->getApplicableShippingServices();
        /** @var ShippingService $toBeDeletedService */
        $toBeDeletedService = $applShipServices->first();

        $configuredMethods = $em->getRepository(ShippingMethodConfig::class)
            ->findBy(['method' => 'ups_' . $ups_channel->getId()]);
        $typesBefore = $em->getRepository(ShippingMethodTypeConfig::class)
            ->findBy(['methodConfig' => $configuredMethods, 'type' => $toBeDeletedService->getCode()]);

        self::assertNotEmpty($typesBefore);

        $ups_transport->removeApplicableShippingService($toBeDeletedService);
        $em->persist($ups_transport);
        $em->flush();

        $typesAfter = $em->getRepository(ShippingMethodTypeConfig::class)
            ->findBy(['methodConfig' => $configuredMethods, 'type' => $toBeDeletedService->getCode()]);

        self::assertEmpty($typesAfter);
    }
}
