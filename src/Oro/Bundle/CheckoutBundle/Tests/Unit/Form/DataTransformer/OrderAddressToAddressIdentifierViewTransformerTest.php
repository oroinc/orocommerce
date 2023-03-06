<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\CheckoutBundle\Form\DataTransformer\OrderAddressToAddressIdentifierViewTransformer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Form\Type\OrderAddressSelectType;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class OrderAddressToAddressIdentifierViewTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var PropertyAccessor */
    private $propertyAccessor;

    /** @var OrderAddressManager|\PHPUnit\Framework\MockObject\MockObject */
    private $addressManager;

    protected function setUp(): void
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->addressManager = $this->createMock(OrderAddressManager::class);
    }

    private function getTransformer(array $requiredFields): OrderAddressToAddressIdentifierViewTransformer
    {
        return new OrderAddressToAddressIdentifierViewTransformer(
            $this->addressManager,
            $this->propertyAccessor,
            $requiredFields
        );
    }

    public function testTransformWhenNotOrderAddress(): void
    {
        $value = new \stdClass();
        $this->assertSame($value, $this->getTransformer([])->transform($value));
    }

    /**
     * @dataProvider transformDataProvider
     *
     * @param OrderAddress $address
     * @param array $requiredFields
     * @param string $expectedIdentifier
     */
    public function testTransform(OrderAddress $address, array $requiredFields, $expectedIdentifier): void
    {
        $this->addressManager
            ->expects($this->any())
            ->method('getIdentifier')
            ->willReturn($expectedIdentifier);

        $this->assertSame($expectedIdentifier, $this->getTransformer($requiredFields)->transform($address));
    }

    public function transformDataProvider(): array
    {
        $orderAddressWithCustomerAddress = new OrderAddress();
        $orderAddressWithCustomerAddress->setCustomerAddress(new CustomerAddress());

        $orderAddressWithCustomerUserAddress = new OrderAddress();
        $orderAddressWithCustomerUserAddress->setCustomerUserAddress(new CustomerUserAddress());

        $orderAddressManual = new OrderAddress();
        $orderAddressManual->setFirstName('SampleFirstName');

        return [
            [
                'address' => $orderAddressWithCustomerAddress,
                'requiredFields' => [],
                'expectedIdentifier' => 'sample_1',
            ],
            [
                'address' => $orderAddressWithCustomerUserAddress,
                'requiredFields' => [],
                'expectedIdentifier' => 'sample_2',
            ],
            [
                'address' => $orderAddressManual,
                'requiredFields' => ['firstName'],
                'expectedIdentifier' => OrderAddressSelectType::ENTER_MANUALLY,
            ],
            [
                'address' => $orderAddressManual,
                'requiredFields' => ['lastName'],
                'expectedIdentifier' => '',
            ],
        ];
    }

    public function testReverseTransform(): void
    {
        $value = 'sampleValue';
        $this->assertSame($value, $this->getTransformer([])->reverseTransform($value));
    }
}
