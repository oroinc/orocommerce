<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Controller\AddressValidation;

use Oro\Bundle\AddressValidationBundle\Test\AddressValidationFeatureAwareTrait;
use Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * @dbIsolationPerTest
 */
final class QuoteAddressValidationControllerTest extends WebTestCase
{
    use RolePermissionExtension;
    use AddressValidationFeatureAwareTrait;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadQuoteData::class, LoadChannelData::class]);
    }

    public function testThatRouteNotFoundWhenFeatureDisabled(): void
    {
        $this->ajaxRequest(
            Request::METHOD_POST,
            $this->getUrl('oro_sale_quote_address_validation_shipping_address', ['id' => 0])
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
            User::ROLE_ADMINISTRATOR,
            Quote::class,
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
            User::ROLE_ADMINISTRATOR,
            Quote::class,
            [
                'EDIT' => $level,
            ]
        );

        $this->ajaxRequest(
            Request::METHOD_POST,
            $this->getUrl($routeName, ['id' => $this->getReference(LoadQuoteData::QUOTE1)->getId()])
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), $statusCode);
    }

    private static function aclProvider(): array
    {
        return [
            ['oro_sale_quote_address_validation_shipping_address', AccessLevel::NONE_LEVEL, 403],
            ['oro_sale_quote_address_validation_shipping_address', AccessLevel::GLOBAL_LEVEL, 200]
        ];
    }
}
