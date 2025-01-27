<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Condition;

use Oro\Bundle\CheckoutBundle\Condition\ValidateCheckoutAddresses;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ValidateCheckoutAddressesTest extends \PHPUnit\Framework\TestCase
{
    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $validator;

    /** @var ValidateCheckoutAddresses */
    private $condition;

    #[\Override]
    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->condition = new ValidateCheckoutAddresses($this->validator);
    }

    public function testInitializeInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "checkout" option');

        self::assertInstanceOf(
            AbstractCondition::class,
            $this->condition->initialize([])
        );
    }

    public function testInitialize(): void
    {
        self::assertInstanceOf(
            AbstractCondition::class,
            $this->condition->initialize(['Method', new \stdClass()])
        );
    }

    public function testGetName(): void
    {
        self::assertEquals('validate_checkout_addresses', $this->condition->getName());
    }

    public function testToArray(): void
    {
        $stdClass = new \stdClass();
        $this->condition->initialize(['checkout' => $stdClass]);
        $result = $this->condition->toArray();

        $key = '@validate_checkout_addresses';

        self::assertIsArray($result);
        self::assertArrayHasKey($key, $result);

        $resultSection = $result[$key];
        self::assertIsArray($resultSection);
        self::assertArrayHasKey('parameters', $resultSection);
        self::assertContains($stdClass, $resultSection['parameters']);
    }

    public function testCompile(): void
    {
        $toStringStub = new ToStringStub();
        $options = ['checkout' => $toStringStub];

        $this->condition->initialize($options);
        $result = $this->condition->compile('$factory');
        self::assertEquals(
            sprintf('$factory->create(\'validate_checkout_addresses\', [%s])', $toStringStub),
            $result
        );
    }

    public function testEvaluateNoCheckoutPassed(): void
    {
        $this->condition->initialize(['checkout' => new \stdClass()]);
        $this->validator->expects(self::never())
            ->method('validate');
        self::assertFalse($this->condition->evaluate([]));
    }

    public function testEvaluateNoBillingAddress(): void
    {
        $this->condition->initialize(['checkout' => new Checkout()]);
        $this->validator->expects(self::never())
            ->method('validate');
        self::assertFalse($this->condition->evaluate([]));
    }

    public function testEvaluateInvalidBillingAddress(): void
    {
        $billingAddress = new OrderAddress();
        $checkout = (new Checkout())->setBillingAddress($billingAddress);
        $this->condition->initialize(['checkout' =>  $checkout]);
        $this->validator->expects(self::once())
            ->method('validate')
            ->willReturn(ConstraintViolationList::createFromMessage('error'));
        self::assertFalse($this->condition->evaluate([]));
    }

    public function testEvaluateCorrectBillingAddressAndShipToBillingEnabled(): void
    {
        $billingAddress = new OrderAddress();
        $checkout = (new Checkout())
            ->setBillingAddress($billingAddress)
            ->setShipToBillingAddress(true);
        $this->condition->initialize(['checkout' =>  $checkout]);
        $this->validator->expects(self::once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());
        self::assertTrue($this->condition->evaluate([]));
    }

    public function testEvaluateEmptyShippingAddress(): void
    {
        $billingAddress = new OrderAddress();
        $checkout = (new Checkout())
            ->setBillingAddress($billingAddress)
            ->setShipToBillingAddress(false);
        $this->condition->initialize(['checkout' =>  $checkout]);
        $this->validator->expects(self::once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());
        self::assertFalse($this->condition->evaluate([]));
    }

    public function testEvaluateEmptyAddresses(): void
    {
        $billingAddress = new OrderAddress();
        $shippingAddress = new OrderAddress();
        $checkout = (new Checkout())
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress)
            ->setShipToBillingAddress(false);
        $this->condition->initialize(['checkout' =>  $checkout]);
        $this->validator->expects(self::once())
            ->method('validate')
            ->willReturn(ConstraintViolationList::createFromMessage('error'));
        self::assertFalse($this->condition->evaluate([]));
    }

    public function testEvaluateInvalidShippingAddress(): void
    {
        $billingAddress = new OrderAddress();
        $checkout = (new Checkout())
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($billingAddress)
            ->setShipToBillingAddress(false);
        $this->condition->initialize(['checkout' =>  $checkout]);
        $this->validator->expects(self::exactly(2))
            ->method('validate')
            ->willReturnOnConsecutiveCalls(
                new ConstraintViolationList(),
                ConstraintViolationList::createFromMessage('error')
            );
        self::assertFalse($this->condition->evaluate([]));
    }

    public function testEvaluateAllAddressesCorrect(): void
    {
        $billingAddress = new OrderAddress();
        $checkout = (new Checkout())
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($billingAddress)
            ->setShipToBillingAddress(false);
        $this->condition->initialize(['checkout' =>  $checkout]);
        $this->validator->expects(self::exactly(2))
            ->method('validate')
            ->willReturn(new ConstraintViolationList());
        self::assertTrue($this->condition->evaluate([]));
    }
}
