<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontendForVisitor\RestJsonApi;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CreateOrderWithNotValidProductKitForVisitorWithGuestCheckoutTest extends FrontendRestJsonApiTestCase
{
    use RolePermissionExtension;

    private ?bool $originalGuestCheckoutOptionValue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableVisitor();
        $this->loadFixtures([
            LoadCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/ApiFrontend/DataFixtures/orders.yml',
            LoadPaymentTermData::class,
        ]);
        $this->originalGuestCheckoutOptionValue = $this->getGuestCheckoutOptionValue();
        if (!$this->originalGuestCheckoutOptionValue) {
            $this->setGuestCheckoutOptionValue(true);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->getGuestCheckoutOptionValue() !== $this->originalGuestCheckoutOptionValue) {
            $this->setGuestCheckoutOptionValue($this->originalGuestCheckoutOptionValue);
        }
        $this->originalGuestCheckoutOptionValue = null;
    }

    protected function getRequestDataFolderName(): string
    {
        return '../../ApiFrontend/RestJsonApi/requests';
    }

    protected function getResponseDataFolderName(): string
    {
        return '../../ApiFrontend/RestJsonApi/responses';
    }

    protected function postFixtureLoad(): void
    {
        parent::postFixtureLoad();
        /** @var PaymentTermAssociationProvider $paymentTermAssociationProvider */
        $paymentTermAssociationProvider = self::getContainer()
            ->get('oro_payment_term.provider.payment_term_association');
        $paymentTermAssociationProvider->setPaymentTerm(
            $this->getGuestCustomerGroup(),
            $this->getReference('payment_term_net_10')
        );
        $this->getEntityManager()->flush();
    }

    private function getGuestCheckoutOptionValue(): bool
    {
        return $this->getConfigManager()->get('oro_checkout.guest_checkout');
    }

    private function setGuestCheckoutOptionValue(bool $value): void
    {
        $configManager = $this->getConfigManager();
        $configManager->set('oro_checkout.guest_checkout', $value);
        $configManager->flush();
    }

    private function getGuestCustomerGroup(): ?CustomerGroup
    {
        /** @var CustomerUserRelationsProvider $customerUserRelationsProvider */
        $customerUserRelationsProvider = self::getContainer()
            ->get('oro_customer.provider.customer_user_relations_provider');

        return $customerUserRelationsProvider->getCustomerGroup();
    }

    public function testTryToCreateWithoutKitItemLineItemProduct(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        unset($data['included'][4]['relationships']['product']);

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'not null constraint',
                    'detail' => 'Please choose a product',
                    'source' => ['pointer' => '/included/4/relationships/product/data'],
                ],
                [
                    'title' => 'not null constraint',
                    'detail' => 'Please choose a product',
                    'source' => ['pointer' => '/included/4/attributes/productSku'],
                ],
                [
                    'title' => 'not null constraint',
                    'detail' => 'Please choose a product',
                    'source' => ['pointer' => '/included/4/attributes/productId'],
                ],
                [
                    'title' => 'not null constraint',
                    'detail' => 'Please choose a product',
                    'source' => ['pointer' => '/included/4/attributes/productName'],
                ],
            ],
            $response
        );
    }

    public function testTryToCreateWithoutKitItemLineItemUnit(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        unset($data['included'][4]['relationships']['productUnit']);

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'not null constraint',
                    'detail' => 'This value should not be null.',
                    'source' => ['pointer' => '/included/4/relationships/productUnit/data'],
                ],
                [
                    'title' => 'not null constraint',
                    'detail' => 'This value should not be null.',
                    'source' => ['pointer' => '/included/4/attributes/productUnitCode'],
                ],
            ],
            $response
        );
    }

    public function testTryToCreateWithWrongProductUnit(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][4]['relationships']['productUnit']['data']['id'] = '<toString(@set->code)>';

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'product kit item line item product unit available constraint',
                    'detail' => 'The selected product unit is not allowed',
                    'source' => [
                        'pointer' => '/included/4/relationships/productUnit/data',
                    ],
                ],
            ],
            $response
        );
    }

    public function testTryToCreateWithFloatQuantityWhenPrecisionIsZero(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][4]['attributes']['quantity'] = 123.45;

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'product kit item line item quantity unit precision constraint',
                    'detail' => 'Only whole numbers are allowed for unit "milliliter"',
                    'source' => ['pointer' => '/included/4/attributes/quantity'],
                ],
            ],
            $response
        );
    }

    public function testTryToCreateWithoutQuantity(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][4]['attributes']['quantity'] = null;

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'not null constraint',
                    'detail' => 'The quantity should be greater than 0',
                    'source' => ['pointer' => '/included/4/attributes/quantity'],
                ],
            ],
            $response
        );
    }

    public function testTryToCreateWithNegativeQuantity(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][4]['attributes']['quantity'] = -1;

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'greater than constraint',
                    'detail' => 'The quantity should be greater than 0',
                    'source' => ['pointer' => '/included/4/attributes/quantity'],
                ],
            ],
            $response
        );
    }

    public function testTryToCreateWithNonFloatQuantity(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][4]['attributes']['quantity'] = 'some string';

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'form constraint',
                    'detail' => 'Please enter a number.',
                    'source' => ['pointer' => '/included/4/attributes/quantity'],
                ],
            ],
            $response
        );
    }

    public function testTryToCreateWithSubmittedPriceThatNotEqualsToCalculatedPrice(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][4]['attributes']['price'] = 9999;

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'price match constraint',
                    'detail' => 'The specified price must be equal to 11.59.',
                    'source' => ['pointer' => '/included/4/attributes/price'],
                ],
            ],
            $response
        );
    }

    public function testTryToCreateWithSubmittedCurrencyThatNotEqualsToCalculatedCurrency(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][4]['attributes']['currency'] = 'EUR';

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'currency match constraint',
                    'detail' => 'The specified currency must be equal to "USD".',
                    'source' => ['pointer' => '/included/4/attributes/currency'],
                ],
            ],
            $response
        );
    }

    public function testTryToCreateWithProductWithoutPrice(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout_with_kit_item_line_item_missing_price.yml');

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'price not found constraint',
                    'detail' => 'No matching price found.',
                    'source' => ['pointer' => '/included/3/attributes/price'],
                ],
                [
                    'title' => 'price not found constraint',
                    'detail' => 'No matching price found.',
                    'source' => ['pointer' => '/included/4/attributes/price'],
                ],
            ],
            $response
        );
    }

    public function testTryToCreateWithoutKitItem(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        unset($data['included'][4]['relationships']['kitItem']);

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'product kit line item contains required kit items constraint',
                    'detail' => 'Product kit "product-kit-1" is missing the required kit item '
                        . '"PKSKU1 - Unit of Quantity Taken from Product Kit"',
                    'source' => ['pointer' => '/included/3/relationships/kitItemLineItems/data'],
                ],
                [
                    'title' => 'not null constraint',
                    'detail' => 'Product kit item must not be blank',
                    'source' => ['pointer' => '/included/4/relationships/kitItem/data'],
                ],
                [
                    'title' => 'not null constraint',
                    'detail' => 'Product kit item must not be blank',
                    'source' => ['pointer' => '/included/4/attributes/kitItemId'],
                ],
                [
                    'title' => 'not null constraint',
                    'detail' => 'Product kit item must not be blank',
                    'source' => ['pointer' => '/included/4/attributes/kitItemLabel'],
                ],
            ],
            $response
        );
    }
}
