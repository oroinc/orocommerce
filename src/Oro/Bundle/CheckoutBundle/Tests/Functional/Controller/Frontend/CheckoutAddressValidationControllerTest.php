<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressValidationBundle\Test\AddressValidationFeatureAwareTrait;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckoutACLData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCompletedAndNonCompletedSimpleCheckoutsData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderAddressData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * @dbIsolationPerTest
 */
final class CheckoutAddressValidationControllerTest extends WebTestCase
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
            LoadCheckoutACLData::class,
            LoadOrderAddressData::class,
            LoadCompletedAndNonCompletedSimpleCheckoutsData::class
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
     * @dataProvider aclMultistepBillingProvider
     */
    public function testAddressValidationBillingAclForUpdating(string $routeName, int $level, int $statusCode): void
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

        /**
         * @var Checkout $checkout
         */
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);

        $this->ajaxRequest(
            Request::METHOD_POST,
            $this->getUrl($routeName, [
                'id' => $checkout->getId(),
            ])
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), $statusCode);
    }

    /**
     * @dataProvider aclMultistepShippingProvider
     */
    public function testAddressValidationShippingAclForUpdating(string $routeName, int $level, int $statusCode): void
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

        /**
         * @var Checkout $checkout
         */
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);
        $address = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1);

        $checkout->setShippingAddress($address);
        $checkout->setBillingAddress($address);

        $this->getDoctrine()->getManagerForClass(Checkout::class)->flush();

        /* @var WorkflowManager $workflowManager */
        $workflowManager = self::getContainer()->get('oro_workflow.manager');

        $workflowItem = $workflowManager->getWorkflowItem($checkout, 'b2b_flow_checkout');
        $workflowManager->transitWithoutChecks($workflowItem, 'continue_to_shipping_address');

        $this->ajaxRequest(
            Request::METHOD_POST,
            $this->getUrl($routeName, [
                'id' => $checkout->getId(),
            ])
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), $statusCode);
    }

    /**
     * @dataProvider aclSinglePageProvider
     */
    public function testAddressValidationSingleStepCheckoutAclForUpdating(
        string $routeName,
        int $level,
        int $statusCode
    ): void {
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

        /**
         * @var Checkout $checkout
         */
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);
        $address = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1);

        $checkout->setShippingAddress($address);
        $checkout->setBillingAddress($address);

        $this->getDoctrine()->getManagerForClass(Checkout::class)->flush();

        /* @var WorkflowManager $workflowManager */
        $workflowManager = self::getContainer()->get('oro_workflow.manager');

        $workflowManager->resetWorkflowItem(
            $workflowManager->getWorkflowItem($checkout, 'b2b_flow_checkout')
        );
        $workflowManager->startWorkflow('b2b_flow_checkout_single_page', $checkout);

        $this->ajaxRequest(
            Request::METHOD_POST,
            $this->getUrl($routeName, [
                'id' => $checkout->getId(),
            ])
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), $statusCode);
    }

    private static function routesProvider(): array
    {
        return [
            ['oro_checkout_address_validation_multi_step_billing_address'],
            ['oro_checkout_address_validation_multi_step_shipping_address'],
            ['oro_checkout_address_validation_single_page_billing_address'],
            ['oro_checkout_address_validation_single_page_shipping_address'],
        ];
    }

    private static function aclMultistepBillingProvider(): array
    {
        return [
            ['oro_checkout_address_validation_multi_step_billing_address', AccessLevel::NONE_LEVEL, 403],
            ['oro_checkout_address_validation_multi_step_billing_address', AccessLevel::GLOBAL_LEVEL, 200],
        ];
    }

    private static function aclMultistepShippingProvider(): array
    {
        return [
            ['oro_checkout_address_validation_multi_step_shipping_address', AccessLevel::NONE_LEVEL, 403],
            ['oro_checkout_address_validation_multi_step_shipping_address', AccessLevel::GLOBAL_LEVEL, 200],
        ];
    }

    private static function aclSinglePageProvider(): array
    {
        return [
            ['oro_checkout_address_validation_single_page_billing_address', AccessLevel::NONE_LEVEL, 403],
            ['oro_checkout_address_validation_single_page_billing_address', AccessLevel::GLOBAL_LEVEL, 200],
            ['oro_checkout_address_validation_single_page_shipping_address', AccessLevel::NONE_LEVEL, 403],
            ['oro_checkout_address_validation_single_page_shipping_address', AccessLevel::GLOBAL_LEVEL, 200]
        ];
    }

    private function getDoctrine(): ManagerRegistry
    {
        return self::getContainer()->get('doctrine');
    }
}
