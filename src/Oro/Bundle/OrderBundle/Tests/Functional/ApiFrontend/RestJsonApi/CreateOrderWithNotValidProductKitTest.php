<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadPaymentTermData;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CreateOrderWithNotValidProductKitTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/ApiFrontend/DataFixtures/orders.yml',
            LoadPaymentTermData::class,
        ]);
    }

    protected function postFixtureLoad()
    {
        parent::postFixtureLoad();
        self::getContainer()->get('oro_payment_term.provider.payment_term_association')
            ->setPaymentTerm($this->getReference('customer'), $this->getReference('payment_term_net_10'));
        $this->getEntityManager()->flush();
    }

    /**
     * @dataProvider getTryToCreateWithWrongFormatOfKitItemLineItemProductDataProvider
     */
    public function testTryToCreateWithWrongFormatOfKitItemLineItemProduct($productData, array $expectedErrors): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][4]['relationships']['product'] = $productData;

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors($expectedErrors, $response);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getTryToCreateWithWrongFormatOfKitItemLineItemProductDataProvider(): array
    {
        return [
            'no data property' => [
                'productData' => [],
                'expectedErrors' => [
                    [
                        'title' => 'request data constraint',
                        'detail' => 'The relationship should have \'data\' property',
                        'source' => ['pointer' => '/included/4/relationships/product'],
                    ],
                ],
            ],
            'data as string' => [
                'productData' => ['data' => 'product'],
                'expectedErrors' => [
                    [
                        'title' => 'request data constraint',
                        'detail' => 'The \'data\' property should be an array',
                        'source' => ['pointer' => '/included/4/relationships/product/data'],
                    ],
                ],
            ],
            'data as non-associative array' => [
                'productData' => ['data' => ['test']],
                'expectedErrors' => [
                    [
                        'title'  => 'request data constraint',
                        'detail' => 'The relationship should be an array',
                        'source' => ['pointer' => '/included/4/relationships/product/data/0'],
                    ],
                ],
            ],
            'data as associative array without type/id properties' => [
                'productData' => ['data' => ['foo' => 'bar']],
                'expectedErrors' => [
                    [
                        'title' => 'request data constraint',
                        'detail' => 'The \'type\' property is required',
                        'source' => ['pointer' => '/included/4/relationships/product/data/type'],
                    ],
                    [
                        'title' => 'request data constraint',
                        'detail' => 'The \'id\' property is required',
                        'source' => ['pointer' => '/included/4/relationships/product/data/id'],
                    ],
                ],
            ],
            'type/id as arrays' => [
                'productData' => ['data' => ['type' => [], 'id' => []]],
                'expectedErrors' => [
                    [
                        'title' => 'request data constraint',
                        'detail' => 'The \'type\' property should be a string',
                        'source' => ['pointer' => '/included/4/relationships/product/data/type'],
                    ],
                    [
                        'title' => 'request data constraint',
                        'detail' => 'The \'id\' property should be a string',
                        'source' => ['pointer' => '/included/4/relationships/product/data/id'],
                    ],
                ],
            ],
            'type/id as integers' => [
                'productData' => ['data' => ['type' => 1, 'id' => 1]],
                'expectedErrors' => [
                    [
                        'title' => 'request data constraint',
                        'detail' => 'The \'type\' property should be a string',
                        'source' => ['pointer' => '/included/4/relationships/product/data/type'],
                    ],
                    [
                        'title' => 'request data constraint',
                        'detail' => 'The \'id\' property should be a string',
                        'source' => ['pointer' => '/included/4/relationships/product/data/id'],
                    ],
                ],
            ],
            'type unknowns' => [
                'productData' => ['data' => ['type' => 'test', 'id' => 'test']],
                'expectedErrors' => [
                    [
                        'title' => 'entity type constraint',
                        'detail' => 'Unknown entity type: test.',
                        'source' => ['pointer' => '/included/4/relationships/product/data/type'],
                    ],
                ],
            ],
            'data as non-associative array in array' => [
                'productData' => ['data' => [['test']]],
                'expectedErrors' => [
                    [
                        'title'  => 'request data constraint',
                        'detail' => 'The \'type\' property is required',
                        'source' => ['pointer' => '/included/4/relationships/product/data/0/type'],
                    ],
                    [
                        'title'  => 'request data constraint',
                        'detail' => 'The \'id\' property is required',
                        'source' => ['pointer' => '/included/4/relationships/product/data/0/id'],
                    ],
                ],
            ],
            'data as associative array without type/id properties in array' => [
                'productData' => ['data' => [['foo' => 'bar']]],
                'expectedErrors' => [
                    [
                        'title'  => 'request data constraint',
                        'detail' => 'The \'type\' property is required',
                        'source' => ['pointer' => '/included/4/relationships/product/data/0/type'],
                    ],
                    [
                        'title'  => 'request data constraint',
                        'detail' => 'The \'id\' property is required',
                        'source' => ['pointer' => '/included/4/relationships/product/data/0/id'],
                    ],
                ],
            ],
            'type/id as arrays in array' => [
                'productData' => ['data' => [['type' => [], 'id' => []]]],
                'expectedErrors' => [
                    [
                        'title'  => 'request data constraint',
                        'detail' => 'The \'type\' property should be a string',
                        'source' => ['pointer' => '/included/4/relationships/product/data/0/type'],
                    ],
                    [
                        'title'  => 'request data constraint',
                        'detail' => 'The \'id\' property should be a string',
                        'source' => ['pointer' => '/included/4/relationships/product/data/0/id'],
                    ],
                ],
            ],
            'type/id as integers in array' => [
                'productData' => ['data' => [['type' => 1, 'id' => 1]]],
                'expectedErrors' => [
                    [
                        'title'  => 'request data constraint',
                        'detail' => 'The \'type\' property should be a string',
                        'source' => ['pointer' => '/included/4/relationships/product/data/0/type'],
                    ],
                    [
                        'title'  => 'request data constraint',
                        'detail' => 'The \'id\' property should be a string',
                        'source' => ['pointer' => '/included/4/relationships/product/data/0/id'],
                    ],
                ],
            ],
            'type unknowns in array' => [
                'productData' => ['data' => [['type' => 'test', 'id' => 'test']]],
                'expectedErrors' => [
                    [
                        'title'  => 'entity type constraint',
                        'detail' => 'Unknown entity type: test.',
                        'source' => ['pointer' => '/included/4/relationships/product/data/0/type'],
                    ],
                ],
            ],
        ];
    }

    public function testTryToCreateWithoutKitItemLineItemProduct(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
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
                    'source' => ['pointer' => '/included/4/attributes/productId'],
                ],
                [
                    'title' => 'not null constraint',
                    'detail' => 'Please choose a product',
                    'source' => ['pointer' => '/included/4/attributes/productSku'],
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
        $data = $this->getRequestData('create_order_min.yml');
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
        $data = $this->getRequestData('create_order_min.yml');
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
        $data = $this->getRequestData('create_order_min.yml');
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
        $data = $this->getRequestData('create_order_min.yml');
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
        $data = $this->getRequestData('create_order_min.yml');
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
        $data = $this->getRequestData('create_order_min.yml');
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
                    'detail' => 'This value is not valid.',
                    'source' => ['pointer' => '/included/4/attributes/quantity'],
                ],
            ],
            $response
        );
    }

    public function testTryToCreateWithSubmittedPriceThatNotEqualsToCalculatedPrice(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
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
                [
                    'title' => 'currency match constraint',
                    'detail' => 'The specified currency must be equal to "USD".',
                    'source' => ['pointer' => '/included/4/attributes/currency'],
                ],
            ],
            $response
        );
    }

    public function testTryToCreateWithSubmittedCurrencyThatNotEqualsToCalculatedCurrency(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
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
                    'title' => 'price match constraint',
                    'detail' => 'The specified price must be equal to 11.59.',
                    'source' => ['pointer' => '/included/4/attributes/price'],
                ],
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
        $data = $this->getRequestData('create_order_with_kit_item_line_item_missing_price.yml');

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
        $data = $this->getRequestData('create_order_min.yml');
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
