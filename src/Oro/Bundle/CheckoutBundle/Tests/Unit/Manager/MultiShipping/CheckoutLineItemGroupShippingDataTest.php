<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Manager\MultiShipping;

use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemGroupShippingData;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CheckoutLineItemGroupShippingDataTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructForEmptyData(): void
    {
        $data = new CheckoutLineItemGroupShippingData();

        self::assertSame([], $data->toArray());
    }

    public function testConstruct(): void
    {
        $shippingData = [
            'product.category:1' => ['method' => 'method1', 'type' => 'type1'],
            'product.category:2' => ['method' => 'method2', 'type' => 'type2', 'amount' => 1.1],
            'product.category:3' => ['method' => 'method3', 'type' => 'type3', 'amount' => 1],
            'product.category:4' => ['amount' => 4.0],
            'product.category:5' => [],
            'other-items'        => ['method' => 'method4', 'type' => 'type4']
        ];
        $data = new CheckoutLineItemGroupShippingData($shippingData);

        self::assertSame(
            [
                'product.category:1' => ['method' => 'method1', 'type' => 'type1'],
                'product.category:2' => ['method' => 'method2', 'type' => 'type2', 'amount' => 1.1],
                'product.category:3' => ['method' => 'method3', 'type' => 'type3', 'amount' => 1.0],
                'other-items'        => ['method' => 'method4', 'type' => 'type4'],
                'product.category:4' => ['amount' => 4.0]
            ],
            $data->toArray()
        );
    }

    public function testConstructWhenLineItemGroupKeyIsNotString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The line item group key must be a string.');

        new CheckoutLineItemGroupShippingData([
            1 => ['method' => 'method1', 'type' => 'type1']
        ]);
    }

    public function testConstructWhenLineItemGroupKeyIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The line item group key must be not an empty string.');

        new CheckoutLineItemGroupShippingData([
            '' => ['method' => 'method1', 'type' => 'type1']
        ]);
    }

    public function testConstructWhenLineItemGroupKeyIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The line item group key must be "field_path:field_value" or "other-items". Given "product.category".'
        );

        new CheckoutLineItemGroupShippingData([
            'product.category' => ['method' => 'method1', 'type' => 'type1']
        ]);
    }

    public function testConstructWhenShippingDataIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The data for "product.category:1" must be an array.');

        new CheckoutLineItemGroupShippingData([
            'product.category:1' => 'test'
        ]);
    }

    public function testConstructWhenNoShippingMethod(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The shipping type for "product.category:1" can be specified only together with the shipping method.'
        );

        new CheckoutLineItemGroupShippingData([
            'product.category:1' => ['type' => 'type1']
        ]);
    }

    public function testConstructWhenShippingMethodIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The shipping method for "product.category:1" must be not an empty string.');

        new CheckoutLineItemGroupShippingData([
            'product.category:1' => ['method' => '', 'type' => 'type1']
        ]);
    }

    public function testConstructWhenShippingMethodIsNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The shipping method for "product.category:1" must be not an empty string.');

        new CheckoutLineItemGroupShippingData([
            'product.category:1' => ['method' => null, 'type' => 'type1']
        ]);
    }

    public function testConstructWhenShippingMethodIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The shipping method for "product.category:1" must be not an empty string.');

        new CheckoutLineItemGroupShippingData([
            'product.category:1' => ['method' => 1, 'type' => 'type1']
        ]);
    }

    public function testConstructWhenNoShippingType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The shipping type for "product.category:1" must be specified.');

        new CheckoutLineItemGroupShippingData([
            'product.category:1' => ['method' => 'method1']
        ]);
    }

    public function testConstructWhenShippingTypeIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The shipping type for "product.category:1" must be not an empty string.');

        new CheckoutLineItemGroupShippingData([
            'product.category:1' => ['method' => 'method1', 'type' => '']
        ]);
    }

    public function testConstructWhenShippingTypeIsNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The shipping type for "product.category:1" must be not an empty string.');

        new CheckoutLineItemGroupShippingData([
            'product.category:1' => ['method' => 'method1', 'type' => null]
        ]);
    }

    public function testConstructWhenShippingTypeIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The shipping type for "product.category:1" must be not an empty string.');

        new CheckoutLineItemGroupShippingData([
            'product.category:1' => ['method' => 'method1', 'type' => 1]
        ]);
    }

    public function testConstructWhenShippingEstimateAmountIsNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The shipping estimate amount for "product.category:1" must be a number.');

        new CheckoutLineItemGroupShippingData([
            'product.category:1' => ['method' => 'method1', 'type' => 'type1', 'amount' => null]
        ]);
    }

    public function testConstructWhenShippingEstimateAmountIsString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The shipping estimate amount for "product.category:1" must be a number.');

        new CheckoutLineItemGroupShippingData([
            'product.category:1' => ['method' => 'method1', 'type' => 'type1', 'amount' => 'invalid']
        ]);
    }

    public function testConstructWhenLineItemsAreGroupedByDifferentFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'All line items should be grouped by the same field path,'
            . ' but detected two different field paths "product.category" and "product.brand".'
        );

        new CheckoutLineItemGroupShippingData([
            'product.category:1' => ['method' => 'method1', 'type' => 'type1'],
            'product.brand:1'    => ['method' => 'method2', 'type' => 'type2']
        ]);
    }

    public function testToArrayForEmptyData(): void
    {
        $data = new CheckoutLineItemGroupShippingData();

        self::assertSame([], $data->toArray());
    }

    public function testToArray(): void
    {
        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingMethod('product.category:1', 'method1', 'type1');
        $data->setShippingMethod('product.category:2', 'method2', 'type2');
        $data->setShippingEstimateAmount('product.category:1', 1.1);

        self::assertSame(
            [
                'product.category:1' => ['method' => 'method1', 'type' => 'type1', 'amount' => 1.1],
                'product.category:2' => ['method' => 'method2', 'type' => 'type2']
            ],
            $data->toArray()
        );
    }

    public function testGetShippingMethodsForEmptyData(): void
    {
        $data = new CheckoutLineItemGroupShippingData();

        self::assertSame([], $data->getShippingMethods());
    }

    public function testGetShippingMethods(): void
    {
        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingMethod('product.category:1', 'method1', 'type1');
        $data->setShippingEstimateAmount('product.category:1', 1.0);

        self::assertEquals(
            ['product.category:1' => ['method' => 'method1', 'type' => 'type1']],
            $data->getShippingMethods()
        );
    }

    public function testSetShippingMethod(): void
    {
        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingMethod('product.category:1', 'method1', 'type1');
        $data->setShippingEstimateAmount('product.category:1', 1.0);
        self::assertSame(
            [
                'product.category:1' => ['method' => 'method1', 'type' => 'type1', 'amount' => 1.0]
            ],
            $data->toArray()
        );

        $data->setShippingMethod('product.category:2', 'method2', 'type2');
        $data->setShippingEstimateAmount('product.category:2', 2.0);
        self::assertSame(
            [
                'product.category:1' => ['method' => 'method1', 'type' => 'type1', 'amount' => 1.0],
                'product.category:2' => ['method' => 'method2', 'type' => 'type2', 'amount' => 2.0]
            ],
            $data->toArray()
        );


        $data->setShippingMethod('product.category:3', 'method3', 'type3');
        self::assertSame(
            [
                'product.category:1' => ['method' => 'method1', 'type' => 'type1', 'amount' => 1.0],
                'product.category:2' => ['method' => 'method2', 'type' => 'type2', 'amount' => 2.0],
                'product.category:3' => ['method' => 'method3', 'type' => 'type3']
            ],
            $data->toArray()
        );

        $data->setShippingMethod('product.category:1', 'method11', 'type11');
        self::assertSame(
            [
                'product.category:1' => ['method' => 'method11', 'type' => 'type11'],
                'product.category:2' => ['method' => 'method2', 'type' => 'type2', 'amount' => 2.0],
                'product.category:3' => ['method' => 'method3', 'type' => 'type3']
            ],
            $data->toArray()
        );
    }

    public function testSetShippingMethodForOtherItemsGroup(): void
    {
        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingMethod('product.category:1', 'method1', 'type1');
        $data->setShippingMethod('other-items', 'method2', 'type2');
        $data->setShippingEstimateAmount('product.category:1', 1.0);
        $data->setShippingEstimateAmount('other-items', 2.0);

        self::assertSame(
            [
                'product.category:1' => ['method' => 'method1', 'type' => 'type1', 'amount' => 1.0],
                'other-items'        => ['method' => 'method2', 'type' => 'type2', 'amount' => 2.0]
            ],
            $data->toArray()
        );
    }

    public function testSetShippingMethodWhenOutdatedDataExist(): void
    {
        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingMethod('product.category:1', 'method1', 'type1');
        $data->setShippingMethod('product.category:2', 'method2', 'type2');
        $data->setShippingMethod('other-items', 'method3', 'type3');
        $data->setShippingEstimateAmount('product.category:1', 1.0);
        $data->setShippingEstimateAmount('product.category:2', 2.0);
        $data->setShippingEstimateAmount('other-items', 3.0);

        $data->setShippingMethod('product.brand:1', 'method1', 'type1');

        self::assertSame(
            [
                'other-items'     => ['method' => 'method3', 'type' => 'type3', 'amount' => 3.0],
                'product.brand:1' => ['method' => 'method1', 'type' => 'type1']
            ],
            $data->toArray()
        );
    }

    public function testSetShippingMethodWhenLineItemGroupKeyIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The line item group key must be not an empty string.');

        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingMethod('', 'method1', 'type1');
    }

    public function testSetShippingMethodWhenLineItemGroupKeyIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The line item group key must be "field_path:field_value" or "other-items". Given "product.category".'
        );

        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingMethod('product.category:1', 'method1', 'type1');

        $data->setShippingMethod('product.category', 'method2', 'type2');
    }

    public function testSetShippingMethodWhenShippingMethodIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The shipping method must be not an empty string.');

        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingMethod('product.category:1', '', 'type1');
    }

    public function testSetShippingMethodWhenShippingTypeIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The shipping type must be not an empty string.');

        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingMethod('product.category:1', 'method1', '');
    }

    public function testRemoveShippingMethod(): void
    {
        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingMethod('product.category:1', 'method1', 'type1');
        $data->setShippingMethod('product.category:2', 'method2', 'type2');
        $data->setShippingMethod('product.category:3', 'method3', 'type3');
        $data->setShippingEstimateAmount('product.category:1', 1.0);
        $data->setShippingEstimateAmount('product.category:2', 2.0);
        $data->setShippingEstimateAmount('product.category:3', 3.0);

        $data->removeShippingMethod('product.category:2');

        self::assertSame(
            [
                'product.category:1' => ['method' => 'method1', 'type' => 'type1', 'amount' => 1.0],
                'product.category:3' => ['method' => 'method3', 'type' => 'type3', 'amount' => 3.0]
            ],
            $data->toArray()
        );
    }

    public function testRemoveLastShippingMethod(): void
    {
        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingMethod('product.category:1', 'method1', 'type1');
        $data->setShippingEstimateAmount('product.category:1', 1.0);

        $data->removeShippingMethod('product.category:1');

        self::assertSame([], $data->toArray());
    }

    public function testRemoveAllShippingMethods(): void
    {
        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingMethod('product.category:1', 'method1', 'type1');
        $data->setShippingEstimateAmount('product.category:1', 1.0);

        $data->removeAllShippingMethods();

        self::assertSame([], $data->toArray());
    }

    public function testGetShippingEstimateAmountsForEmptyData(): void
    {
        $data = new CheckoutLineItemGroupShippingData();

        self::assertSame([], $data->getShippingEstimateAmounts());
    }

    public function testGetShippingEstimateAmounts(): void
    {
        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingEstimateAmount('product.category:1', 1.0);

        self::assertSame(
            ['product.category:1' => 1.0],
            $data->getShippingEstimateAmounts()
        );
    }

    public function testSetShippingEstimateAmount(): void
    {
        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingEstimateAmount('product.category:1', 1.0);
        $data->setShippingEstimateAmount('product.category:2', 2.0);
        $data->setShippingEstimateAmount('product.category:1', 1.1);

        self::assertSame(
            [
                'product.category:1' => 1.1,
                'product.category:2' => 2.0
            ],
            $data->getShippingEstimateAmounts()
        );
    }

    public function testSetShippingEstimateAmountForOtherItemsGroup(): void
    {
        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingEstimateAmount('product.category:1', 1.0);
        $data->setShippingEstimateAmount('other-items', 2.0);

        self::assertSame(
            [
                'product.category:1' => 1.0,
                'other-items'        => 2.0
            ],
            $data->getShippingEstimateAmounts()
        );
    }

    public function testSetShippingEstimateAmountWhenOutdatedDataExist(): void
    {
        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingEstimateAmount('product.category:1', 1.0);
        $data->setShippingEstimateAmount('product.category:2', 2.0);
        $data->setShippingEstimateAmount('other-items', 3.0);

        $data->setShippingEstimateAmount('product.brand:1', 1.1);

        self::assertSame(
            [
                'other-items'     => 3.0,
                'product.brand:1' => 1.1
            ],
            $data->getShippingEstimateAmounts()
        );
    }

    public function testSetShippingEstimateAmountWhenLineItemGroupKeyIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The line item group key must be not an empty string.');

        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingEstimateAmount('', 1.0);
    }

    public function testSetShippingEstimateAmountWhenLineItemGroupKeyIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The line item group key must be "field_path:field_value" or "other-items". Given "product.category".'
        );

        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingEstimateAmount('product.category:1', 1.0);

        $data->setShippingEstimateAmount('product.category', 2.0);
    }

    public function testRemoveShippingEstimateAmount(): void
    {
        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingEstimateAmount('product.category:1', 1.0);
        $data->setShippingEstimateAmount('product.category:2', 2.0);
        $data->setShippingEstimateAmount('product.category:3', 3.0);

        $data->removeShippingEstimateAmount('product.category:2');

        self::assertSame(
            [
                'product.category:1' => 1.0,
                'product.category:3' => 3.0
            ],
            $data->getShippingEstimateAmounts()
        );
    }

    public function testRemoveLastShippingEstimateAmount(): void
    {
        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingEstimateAmount('product.category:1', 1.0);

        $data->removeShippingEstimateAmount('product.category:1');

        self::assertSame([], $data->toArray());
    }

    public function testRemoveAllShippingEstimateAmounts(): void
    {
        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingEstimateAmount('product.category:1', 1.0);

        $data->removeAllShippingEstimateAmounts();

        self::assertSame([], $data->toArray());
    }

    public function testClear(): void
    {
        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingMethod('product.category:1', 'method1', 'type1');
        $data->setShippingEstimateAmount('product.category:1', 1.1);

        $data->clear();

        self::assertSame([], $data->getShippingMethods());
        self::assertSame([], $data->getShippingEstimateAmounts());
    }

    public function testIsEmpty(): void
    {
        $data = new CheckoutLineItemGroupShippingData();
        self::assertTrue($data->isEmpty());

        $data->setShippingMethod('product.category:1', 'method1', 'type1');
        self::assertFalse($data->isEmpty());

        $data = new CheckoutLineItemGroupShippingData();
        $data->setShippingEstimateAmount('product.category:1', 1.1);
        self::assertFalse($data->isEmpty());
    }
}
