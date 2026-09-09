<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\Controller;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class OrderReorderAclTest extends WebTestCase
{
    use RolePermissionExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->followRedirects(true);
        $this->loadFixtures([LoadOrders::class]);
    }

    public function testReorderWhenViewIsGranted(): void
    {
        $this->client->request(Request::METHOD_GET, $this->getReorderUrl());

        self::assertNotSame(
            Response::HTTP_FORBIDDEN,
            $this->client->getResponse()->getStatusCode(),
            'Reorder must not be rejected when the source order is readable'
        );
    }

    public function testReorderWhenViewIsDenied(): void
    {
        $this->updateRolePermission(
            'ROLE_ADMINISTRATOR',
            Order::class,
            AccessLevel::NONE_LEVEL,
            'VIEW'
        );

        $this->client->request(Request::METHOD_GET, $this->getReorderUrl());

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }

    private function getReorderUrl(): string
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        return $this->getUrl('oro_order_reorder', ['id' => $order->getId()]);
    }
}
