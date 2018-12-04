<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Condition;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Condition\ToStringStub;
use Oro\Bundle\ConsentBundle\Condition\CheckoutHasUnacceptedConsents;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Extractor\CustomerUserExtractor;
use Oro\Bundle\ConsentBundle\Provider\ConsentDataProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutHasUnacceptedConsentsTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var CheckoutHasUnacceptedConsents */
    protected $condition;

    /** @var ConsentDataProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $consentsProvider;

    /** @var CustomerUserExtractor */
    protected $customerUserExtractor;

    protected function setUp()
    {
        $this->consentsProvider = $this->createMock(ConsentDataProvider::class);

        $this->customerUserExtractor = new CustomerUserExtractor();
        $this->customerUserExtractor->addMapping(Checkout::class, 'customerUser');
        $this->customerUserExtractor->addMapping(Checkout::class, 'registeredCustomerUser');

        $this->condition = new CheckoutHasUnacceptedConsents(
            $this->consentsProvider,
            $this->customerUserExtractor
        );
    }

    public function testGetName()
    {
        $this->assertEquals(CheckoutHasUnacceptedConsents::NAME, $this->condition->getName());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Missing "checkout" option
     */
    public function testInitializeInvalid()
    {
        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize([])
        );
    }

    public function testInitializeCheckoutInOptions()
    {
        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize(['checkout' => new \stdClass()])
        );
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Missing "checkout" option
     */
    public function testInitializeEmptyCheckoutInOptions()
    {
        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize(['checkout' => null])
        );
    }

    public function testInitializeArrayInOptions()
    {
        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize([new \stdClass()])
        );
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Missing "checkout" option
     */
    public function testInitializeArrayWithFalseValueInOptions()
    {
        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize([0 => false])
        );
    }

    /**
     * @dataProvider evaluateProvider
     *
     * @param Checkout     $checkout
     * @param CustomerUser $customerUser
     * @param array        $consents
     * @param              $expected
     */
    public function testEvaluate(Checkout $checkout, CustomerUser $customerUser, array $consents, $expected)
    {
        $this->consentsProvider->expects($this->once())
            ->method('getNotAcceptedRequiredConsentData')
            ->with($customerUser)
            ->willReturn($consents);

        $this->condition->initialize(['checkout' => $checkout]);
        $this->assertEquals($expected, $this->condition->evaluate([]));
    }

    /**
     * @return array
     */
    public function evaluateProvider()
    {
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 1]);
        $checkoutWithCustomerUser = $this->getEntity(
            Checkout::class,
            [
                'customerUser' => $customerUser
            ]
        );
        $checkoutWithRegisteredCustomerUser = $this->getEntity(
            Checkout::class,
            [
                'registeredCustomerUser' => $customerUser
            ]
        );

        return [
            'no_unaccepted_consents and property "customerUser" in use' => [
                'checkout' => $checkoutWithCustomerUser,
                'customerUser' => $customerUser,
                'consents' => [],
                'expected' => false,
            ],
            'has_unaccepted_consents and property "customerUser" in use' => [
                'checkout' => $checkoutWithCustomerUser,
                'customerUser' => $customerUser,
                'consents' => [new Consent(), new Consent()],
                'expected' => true,
            ],
            'no_unaccepted_consents and property "registeredCustomerUser" in use' => [
                'checkout' => $checkoutWithRegisteredCustomerUser,
                'customerUser' => $customerUser,
                'consents' => [],
                'expected' => false,
            ],
            'has_unaccepted_consents and property "registeredCustomerUser" in use' => [
                'checkout' => $checkoutWithRegisteredCustomerUser,
                'customerUser' => $customerUser,
                'consents' => [new Consent(), new Consent()],
                'expected' => true,
            ],
        ];
    }

    public function testEvaluateNoCustomerUser()
    {
        $checkout = $this->getEntity(Checkout::class);

        $this->consentsProvider->expects($this->never())
            ->method('getNotAcceptedRequiredConsentData');

        $this->condition->initialize(['checkout' => $checkout]);
        $this->assertEquals(true, $this->condition->evaluate([]));
    }

    public function testToArray()
    {
        $stdClass = new \stdClass();
        $this->condition->initialize(['checkout' => $stdClass]);
        $result = $this->condition->toArray();

        $key = '@'.CheckoutHasUnacceptedConsents::NAME;

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey($key, $result);

        $resultSection = $result[$key];
        $this->assertInternalType('array', $resultSection);
        $this->assertArrayHasKey('parameters', $resultSection);
        $this->assertContains($stdClass, $resultSection['parameters']);
    }

    public function testCompile()
    {
        $toStringStub = new ToStringStub();
        $options = ['checkout' => $toStringStub];

        $this->condition->initialize($options);
        $result = $this->condition->compile('$factory');
        $this->assertEquals(
            sprintf(
                '$factory->create(\'%s\', [%s])',
                CheckoutHasUnacceptedConsents::NAME,
                $toStringStub
            ),
            $result
        );
    }
}
