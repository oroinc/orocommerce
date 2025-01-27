<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Controller\AddressValidation;

use Oro\Bundle\AddressValidationBundle\Test\AddressValidationFeatureAwareTrait;
use Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

final class OrderAddressValidationControllerTest extends WebTestCase
{
    use RolePermissionExtension;
    use AddressValidationFeatureAwareTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadOrders::class,
            LoadChannelData::class
        ]);
    }

    /**
     * @dataProvider routesProvider
     */
    public function testThatRouteNotFoundWhenFeatureDisabled(string $routeName): void
    {
        $this->ajaxRequest(
            Request::METHOD_POST,
            $this->getUrl($routeName, ['id' => 0])
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), 404);
    }

    /**
     * @dataProvider aclProvider
     */
    public function testAddressValidationAclForCreating(string $routeName, int $level, int $statusCode): void
    {
        self::enableAddressValidationFeature(
            $this->getReference('oro_integration:foo_integration')->getId()
        );

        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            Order::class,
            [
                'CREATE' => $level,
            ]
        );

        $this->ajaxRequest(
            Request::METHOD_POST,
            $this->getUrl($routeName, ['id' => 0])
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), $statusCode);
    }

    /**
     * @dataProvider aclProvider
     */
    public function testThatValidationIsForbiddenForUpdating(string $routeName, int $level, int $statusCode): void
    {
        self::enableAddressValidationFeature(
            $this->getReference('oro_integration:foo_integration')->getId()
        );

        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            Order::class,
            [
                'EDIT' => $level,
            ]
        );

        $this->ajaxRequest(
            Request::METHOD_POST,
            $this->getUrl($routeName, ['id' => $this->getReference(LoadOrders::ORDER_1)->getId()])
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), $statusCode);
    }

    private static function routesProvider(): array
    {
        return [
            ['oro_order_address_validation_shipping_address'],
            ['oro_order_address_validation_billing_address']
        ];
    }

    private static function aclProvider(): array
    {
        return [
            ['oro_order_address_validation_shipping_address', AccessLevel::NONE_LEVEL, 403],
            ['oro_order_address_validation_shipping_address', AccessLevel::GLOBAL_LEVEL, 200],
            ['oro_order_address_validation_billing_address', AccessLevel::NONE_LEVEL, 403],
            ['oro_order_address_validation_billing_address', AccessLevel::GLOBAL_LEVEL, 200],
        ];
    }
}
