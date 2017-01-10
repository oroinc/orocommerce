<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Datagrid;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\EventListener\ValueRenderEventListener;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Component\Testing\Unit\EntityTrait;

use Symfony\Bundle\FrameworkBundle\Tests\Templating\Helper\Fixtures\StubTranslator;
use Symfony\Component\Routing\RouterInterface;

class ValueRenderEventListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ValueRenderEventListener */
    private $valueRenderEventListener;

    /** @var PaymentTermAssociationProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $associationProvider;

    /** @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $router;

    protected function setUp()
    {
        $this->associationProvider = $this->getMockBuilder(PaymentTermAssociationProvider::class)
            ->disableOriginalConstructor()->getMock();

        $this->router = $this->createMock(RouterInterface::class);
        $this->router->expects($this->any())->method('generate')->willReturnCallback(
            function ($routeName, array $routeParams) {
                $this->assertArrayHasKey('id', $routeParams);

                return $routeName.'-'.$routeParams['id'];
            }
        );

        $this->valueRenderEventListener = new ValueRenderEventListener(
            $this->associationProvider,
            new StubTranslator(),
            $this->router
        );
    }

    public function testBeforeValueRenderNotAccount()
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

        $this->associationProvider->expects($this->once())->method('getAssociationNames')->willReturn(['paymentTerm']);

        $this->valueRenderEventListener->beforeValueRender($event);
        $this->assertSame('value', $event->getFieldViewValue());
    }

    public function testBeforeValueRenderFieldAccountHasDefaultPaymentTerm()
    {
        $event = new ValueRenderEvent(
            new Customer(),
            null,
            new FieldConfigId('scope', Customer::class, 'paymentTerm', 'entity')
        );

        $paymentTerm = $this->getEntity(PaymentTerm::class, ['id' => 1, 'label' => 'pt']);
        $this->associationProvider->expects($this->once())->method('getAssociationNames')->willReturn(['paymentTerm']);
        $this->associationProvider->expects($this->once())->method('getPaymentTerm')->willReturn($paymentTerm);

        $this->valueRenderEventListener->beforeValueRender($event);
        $this->assertSame(['title' => 'pt', 'link' => 'oro_payment_term_view-1'], $event->getFieldViewValue());
    }

    public function testBeforeValueRenderFieldAccountHasNotDefaultPaymentTermAndWithoutGroup()
    {
        $event = new ValueRenderEvent(
            new Customer(),
            null,
            new FieldConfigId('scope', Customer::class, 'paymentTerm', 'entity')
        );

        $this->associationProvider->expects($this->once())->method('getAssociationNames')->willReturn(['paymentTerm']);
        $this->associationProvider->expects($this->once())->method('getPaymentTerm')->willReturn(null);

        $this->valueRenderEventListener->beforeValueRender($event);
        $this->assertNull($event->getFieldViewValue());
    }

    public function testBeforeValueRenderFieldAccountHasNotDefaultPaymentTermAndEmptyWithGroupAssociations()
    {
        $entity = (new Customer())->setGroup(new CustomerGroup());
        $event = new ValueRenderEvent(
            $entity,
            null,
            new FieldConfigId('scope', Customer::class, 'paymentTerm', 'entity')
        );

        $this->associationProvider->expects($this->exactly(2))->method('getAssociationNames')
            ->willReturnOnConsecutiveCalls(['paymentTerm'], []);

        $this->associationProvider->expects($this->once())->method('getPaymentTerm')->willReturn(null);

        $this->valueRenderEventListener->beforeValueRender($event);
        $this->assertNull($event->getFieldViewValue());
    }

    public function testBeforeValueRenderFieldAccountHasNotDefaultPaymentTermAndWithGroupAssociationsWithoutGroupPT()
    {
        $entity = (new Customer())->setGroup(new CustomerGroup());
        $event = new ValueRenderEvent(
            $entity,
            null,
            new FieldConfigId('scope', Customer::class, 'paymentTerm', 'entity')
        );

        $this->associationProvider->expects($this->exactly(2))->method('getAssociationNames')
            ->willReturnOnConsecutiveCalls(['paymentTerm'], ['groupPaymentTerm']);

        $this->associationProvider->expects($this->exactly(2))->method('getPaymentTerm')->willReturn(null);

        $this->valueRenderEventListener->beforeValueRender($event);
        $this->assertNull($event->getFieldViewValue());
    }

    public function testBeforeValueRenderFieldAccountHasNotDefaultPaymentTermAndWithGroupAssociations()
    {
        $entity = (new Customer())->setGroup(new CustomerGroup());
        $event = new ValueRenderEvent(
            $entity,
            null,
            new FieldConfigId('scope', Customer::class, 'paymentTerm', 'entity')
        );

        $this->associationProvider->expects($this->exactly(2))->method('getAssociationNames')
            ->willReturnOnConsecutiveCalls(['paymentTerm'], ['groupPaymentTerm']);

        $paymentTerm = $this->getEntity(PaymentTerm::class, ['id' => 4, 'label' => 'groupPaymentTerm']);
        $this->associationProvider->expects($this->exactly(2))->method('getPaymentTerm')
            ->willReturnOnConsecutiveCalls(null, $paymentTerm);

        $this->valueRenderEventListener->beforeValueRender($event);
        $this->assertEquals(
            [
                'title' => '[trans]oro.paymentterm.account.account_group_defined[/trans]',
                'link' => 'oro_payment_term_view-4',
            ],
            $event->getFieldViewValue()
        );
    }
}
