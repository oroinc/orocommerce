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
    /** @var OrderAddressManager|\PHPUnit\Framework\MockObject\MockObject */
    private $addressManager;

    /** @var PropertyAccessor */
    private $propertyAccessor;

    #[\Override]
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
        self::assertSame($value, $this->getTransformer([])->transform($value));
    }

    public function testTransformForOrderAddressWithCustomerAddress(): void
    {
        $address = new OrderAddress();
        $address->setCustomerAddress(new CustomerAddress());
        $identifier = 'sample_1';

        $this->addressManager->expects(self::once())
            ->method('getIdentifier')
            ->willReturn($identifier);

        self::assertSame($identifier, $this->getTransformer([])->transform($address));
    }

    public function testTransformForOrderAddressWithCustomerUserAddress(): void
    {
        $address = new OrderAddress();
        $address->setCustomerUserAddress(new CustomerUserAddress());
        $identifier = 'sample_1';

        $this->addressManager->expects(self::once())
            ->method('getIdentifier')
            ->willReturn($identifier);

        self::assertSame($identifier, $this->getTransformer([])->transform($address));
    }

    public function testTransformForOrderAddressEnteredManual(): void
    {
        $address = new OrderAddress();
        $address->setFirstName('SampleFirstName');

        $this->addressManager->expects(self::never())
            ->method('getIdentifier');

        self::assertSame(
            OrderAddressSelectType::ENTER_MANUALLY,
            $this->getTransformer(['firstName'])->transform($address)
        );
    }

    public function testTransformForOrderAddressEnteredManualAndRequiredFieldsAreNotFilled(): void
    {
        $address = new OrderAddress();
        $address->setFirstName('SampleFirstName');

        $this->addressManager->expects(self::never())
            ->method('getIdentifier');

        self::assertSame('', $this->getTransformer(['lastName'])->transform($address));
    }

    public function testReverseTransform(): void
    {
        $value = 'sampleValue';
        self::assertSame($value, $this->getTransformer([])->reverseTransform($value));
    }
}
