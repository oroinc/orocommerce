<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Condition;

use Oro\Bundle\CheckoutBundle\Condition\CheckEmailConfirmation;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CheckEmailConfirmationTest extends TestCase
{
    /** @var CheckEmailConfirmation */
    private $condition;

    protected function setUp(): void
    {
        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker
            ->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturn(false); // true - skip email confirmation.

        $this->condition = new CheckEmailConfirmation();
        $this->condition->setFeatureChecker($featureChecker);
        $this->condition->addFeature('allow_checkout_without_email_confirmation_feature');
    }

    public function testInitialize(): void
    {
        $options = ['checkout' => new Checkout()];

        $this->assertInstanceOf(AbstractCondition::class, $this->condition->initialize($options));
    }

    public function testInitializeWithoutCheckout(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "checkout" option');

        $this->condition->initialize([]);
    }

    /**
     * @dataProvider evaluateProvider
     */
    public function testEvaluate(?CustomerUser $customerUser, bool $expected): void
    {
        $checkout = new Checkout();
        if ($customerUser) {
            $checkout->setRegisteredCustomerUser($customerUser);
        }

        $options = ['checkout' => $checkout];
        $this->condition->initialize($options);
        $this->assertEquals($expected, $this->condition->evaluate([]));
    }

    public function evaluateProvider(): array
    {
        return [
            [(new CustomerUser())->setConfirmed(true), true],
            [(new CustomerUser())->setConfirmed(false), false],
            [null, true]
        ];
    }

    public function testGetName(): void
    {
        $this->assertEquals('is_email_confirmed', $this->condition->getName());
    }

    public function testToArray(): void
    {
        $options = ['checkout' => new Checkout()];
        $this->condition->initialize($options);
        $result = $this->condition->toArray();

        $key = '@' . CheckEmailConfirmation::NAME;

        $this->assertIsArray($result);
        $this->assertArrayHasKey($key, $result);

        $resultSection = $result[$key];
        $this->assertIsArray($resultSection);
        $this->assertArrayHasKey('parameters', $resultSection);
        $this->assertContains($options['checkout'], $resultSection['parameters']);
    }

    public function testCompile(): void
    {
        $toStringStub = new ToStringStub();
        $options = ['checkout' => $toStringStub];

        $this->condition->initialize($options);
        $result = $this->condition->compile('$factory');
        $this->assertEquals(
            sprintf(
                '$factory->create(\'%s\', [%s])->setMessage(\'oro.checkout.confirm_email_flash_message\')',
                CheckEmailConfirmation::NAME,
                $toStringStub
            ),
            $result
        );
    }
}
