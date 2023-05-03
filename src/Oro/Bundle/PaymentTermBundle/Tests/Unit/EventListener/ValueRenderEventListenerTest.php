<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\EventListener\ValueRenderEventListener;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ValueRenderEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var PaymentTermAssociationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $associationProvider;

    /** @var ValueRenderEventListener */
    private $valueRenderEventListener;

    protected function setUp(): void
    {
        $this->associationProvider = $this->createMock(PaymentTermAssociationProvider::class);

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->any())
            ->method('generate')
            ->willReturnCallback(function ($routeName, array $routeParams) {
                $this->assertArrayHasKey('id', $routeParams);

                return $routeName . '-' . $routeParams['id'];
            });

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(static function (string $key) {
                return sprintf('[trans]%s[/trans]', $key);
            });

        $this->valueRenderEventListener = new ValueRenderEventListener(
            $this->associationProvider,
            $translator,
            $router
        );
    }

    public function testBeforeValueRenderNotCustomer()
    {
        $event = new ValueRenderEvent(
            new \stdClass(),
            'value',
            new FieldConfigId('scope', \stdClass::class, 'field', 'string')
        );

        $this->valueRenderEventListener->beforeValueRender($event);
        $this->assertSame('value', $event->getFieldViewValue());
    }

    public function testBeforeValueRenderFieldIsNotPaymentTerm()
    {
        $event = new ValueRenderEvent(
            new Customer(),
            'value',
            new FieldConfigId('scope', Customer::class, 'field', 'string')
        );

        $this->associationProvider->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['paymentTerm']);

        $this->valueRenderEventListener->beforeValueRender($event);
        $this->assertSame('value', $event->getFieldViewValue());
    }

    public function testBeforeValueRenderFieldCustomerHasDefaultPaymentTerm()
    {
        $event = new ValueRenderEvent(
            new Customer(),
            null,
            new FieldConfigId('scope', Customer::class, 'paymentTerm', 'entity')
        );

        $paymentTerm = $this->getEntity(PaymentTerm::class, ['id' => 1, 'label' => 'pt']);
        $this->associationProvider->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['paymentTerm']);
        $this->associationProvider->expects($this->once())
            ->method('getPaymentTerm')
            ->willReturn($paymentTerm);

        $this->valueRenderEventListener->beforeValueRender($event);
        $this->assertSame(['title' => 'pt', 'link' => 'oro_payment_term_view-1'], $event->getFieldViewValue());
    }

    public function testBeforeValueRenderFieldCustomerHasNotDefaultPaymentTermAndWithoutGroup()
    {
        $event = new ValueRenderEvent(
            new Customer(),
            null,
            new FieldConfigId('scope', Customer::class, 'paymentTerm', 'entity')
        );

        $this->associationProvider->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['paymentTerm']);
        $this->associationProvider->expects($this->once())
            ->method('getPaymentTerm')
            ->willReturn(null);

        $this->valueRenderEventListener->beforeValueRender($event);
        $this->assertNull($event->getFieldViewValue());
    }

    public function testBeforeValueRenderFieldCustomerHasNotDefaultPaymentTermAndEmptyWithGroupAssociations()
    {
        $entity = (new Customer())->setGroup(new CustomerGroup());
        $event = new ValueRenderEvent(
            $entity,
            null,
            new FieldConfigId('scope', Customer::class, 'paymentTerm', 'entity')
        );

        $this->associationProvider->expects($this->exactly(2))
            ->method('getAssociationNames')
            ->willReturnOnConsecutiveCalls(['paymentTerm'], []);

        $this->associationProvider->expects($this->once())
            ->method('getPaymentTerm')
            ->willReturn(null);

        $this->valueRenderEventListener->beforeValueRender($event);
        $this->assertNull($event->getFieldViewValue());
    }

    public function testBeforeValueRenderFieldCustomerHasNotDefaultPaymentTermAndWithGroupAssociationsWithoutGroupPT()
    {
        $entity = (new Customer())->setGroup(new CustomerGroup());
        $event = new ValueRenderEvent(
            $entity,
            null,
            new FieldConfigId('scope', Customer::class, 'paymentTerm', 'entity')
        );

        $this->associationProvider->expects($this->exactly(2))
            ->method('getAssociationNames')
            ->willReturnOnConsecutiveCalls(['paymentTerm'], ['groupPaymentTerm']);

        $this->associationProvider->expects($this->exactly(2))
            ->method('getPaymentTerm')
            ->willReturn(null);

        $this->valueRenderEventListener->beforeValueRender($event);
        $this->assertNull($event->getFieldViewValue());
    }

    public function testBeforeValueRenderFieldCustomerHasNotDefaultPaymentTermAndWithGroupAssociations()
    {
        $entity = (new Customer())->setGroup(new CustomerGroup());
        $event = new ValueRenderEvent(
            $entity,
            null,
            new FieldConfigId('scope', Customer::class, 'paymentTerm', 'entity')
        );

        $this->associationProvider->expects($this->exactly(2))
            ->method('getAssociationNames')
            ->willReturnOnConsecutiveCalls(['paymentTerm'], ['groupPaymentTerm']);

        $paymentTerm = $this->getEntity(PaymentTerm::class, ['id' => 4, 'label' => 'groupPaymentTerm']);
        $this->associationProvider->expects($this->exactly(2))
            ->method('getPaymentTerm')
            ->willReturnOnConsecutiveCalls(null, $paymentTerm);

        $this->valueRenderEventListener->beforeValueRender($event);
        $this->assertEquals(
            [
                'title' => '[trans]oro.paymentterm.customer.customer_group_defined[/trans]',
                'link' => 'oro_payment_term_view-4',
            ],
            $event->getFieldViewValue()
        );
    }
}
