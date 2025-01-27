<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\AddressValidationBundle\Test\AddressValidationFeatureAwareTrait;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckoutACLData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

final class CheckoutAddressValidationNewAddressControllerTest extends WebTestCase
{
    use RolePermissionExtension;
    use AddressValidationFeatureAwareTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(
                LoadCustomerUserData::AUTH_USER,
                LoadCustomerUserData::AUTH_PW
            )
        );

        $this->loadFixtures([
            LoadChannelData::class,
            LoadCheckoutACLData::class
        ]);
    }

    /**
     * @dataProvider routesProvider
     */
    public function testThatRouteNotFoundWhenFeatureDisabled(string $routeName): void
    {
        $this->ajaxRequest(
            Request::METHOD_POST,
            $this->getUrl(
                $routeName,
                ['id' => $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL)->getId()]
            )
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), 404);
    }

    /**
     * @dataProvider aclProvider
     */
    public function testAddressValidationAclForUpdating(string $routeName, int $level, int $statusCode): void
    {
        self::enableAddressValidationFeature(
            $this->getReference('oro_integration:foo_integration')->getId()
        );

        $this->updateRolePermissions(
            'ROLE_FRONTEND_ADMINISTRATOR',
            Checkout::class,
            [
                'EDIT' => $level,
            ]
        );

        $this->ajaxRequest(
            Request::METHOD_POST,
            $this->getUrl($routeName, [
                'id' => $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL)->getId(),
            ])
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), $statusCode);
    }

    private static function routesProvider(): array
    {
        return [
            ['oro_checkout_address_validation_single_page_new_billing_address'],
            ['oro_checkout_address_validation_single_page_new_shipping_address'],
        ];
    }

    private static function aclProvider(): array
    {
        return [
            ['oro_checkout_address_validation_single_page_new_billing_address', AccessLevel::NONE_LEVEL, 403],
            ['oro_checkout_address_validation_single_page_new_billing_address', AccessLevel::GLOBAL_LEVEL, 200],
            ['oro_checkout_address_validation_single_page_new_shipping_address', AccessLevel::NONE_LEVEL, 403],
            ['oro_checkout_address_validation_single_page_new_shipping_address', AccessLevel::GLOBAL_LEVEL, 200],
        ];
    }
}
