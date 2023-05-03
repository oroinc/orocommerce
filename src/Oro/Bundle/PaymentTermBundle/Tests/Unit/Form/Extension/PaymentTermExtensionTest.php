<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Form\Extension\PaymentTermExtension;
use Oro\Bundle\PaymentTermBundle\Form\Type\PaymentTermSelectType;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Oro\Bundle\PaymentTermBundle\Tests\Unit\PaymentTermAwareStub;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;

class PaymentTermExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var PaymentTermProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentTermProvider;

    /** @var PaymentTermExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->paymentTermProvider = $this->createMock(PaymentTermProvider::class);

        $this->extension = new PaymentTermExtension($this->paymentTermProvider);
    }

    private function getPaymentTerm(int $id): PaymentTerm
    {
        $paymentTerm = new PaymentTerm();
        ReflectionUtil::setId($paymentTerm, $id);

        return $paymentTerm;
    }

    private function addCallbackAssert(
        FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder,
        FormEvent $formEvent
    ): void {
        $builder->expects($this->once())
            ->method('addEventListener')
            ->with(
                $this->logicalAnd(
                    $this->isType('string'),
                    $this->equalTo('form.post_set_data')
                ),
                $this->logicalAnd(
                    $this->isInstanceOf(\Closure::class),
                    $this->callback(function (\Closure $closure) use ($formEvent) {
                        $closure($formEvent);

                        return true;
                    })
                )
            );
    }

    public function testGetExtendedTypes()
    {
        $this->assertSame([PaymentTermSelectType::class], PaymentTermExtension::getExtendedTypes());
    }

    public function testBuildWithoutParent()
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getParent')
            ->willReturn(null);
        $formEvent = new FormEvent($form, new PaymentTermAwareStub());
        $builder = $this->createMock(FormBuilderInterface::class);

        $this->addCallbackAssert($builder, $formEvent);

        $this->extension->buildForm($builder, []);
    }

    public function testBuildWithoutParentData()
    {
        $parent = $this->createMock(FormInterface::class);
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getParent')
            ->willReturn($parent);
        $parent->expects($this->once())
            ->method('getData')
            ->willReturn(null);
        $formEvent = new FormEvent($form, new PaymentTermAwareStub());
        $builder = $this->createMock(FormBuilderInterface::class);

        $this->addCallbackAssert($builder, $formEvent);

        $this->extension->buildForm($builder, []);
    }

    public function testBuildParentDataNotCustomerOwnerAwareInterface()
    {
        $parent = $this->createMock(FormInterface::class);
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getParent')
            ->willReturn($parent);
        $parent->expects($this->once())
            ->method('getData')
            ->willReturn(new \stdClass());
        $formEvent = new FormEvent($form, new PaymentTermAwareStub());
        $builder = $this->createMock(FormBuilderInterface::class);

        $this->addCallbackAssert($builder, $formEvent);

        $this->extension->buildForm($builder, []);
    }

    /**
     * @dataProvider parentDataProvider
     */
    public function testBuildParentDataReplacePaymentTermAttributes(
        ?PaymentTerm $customerPaymentTerm,
        ?PaymentTerm $customerGroupPaymentTerm,
        array $expected
    ) {
        $this->paymentTermProvider->expects($this->once())
            ->method('getCustomerPaymentTermByOwner')
            ->willReturn($customerPaymentTerm);
        $this->paymentTermProvider->expects($this->once())
            ->method('getCustomerGroupPaymentTermByOwner')
            ->willReturn($customerGroupPaymentTerm);

        $parent = $this->createMock(FormInterface::class);
        $parent->expects($this->once())
            ->method('getData')
            ->willReturn(new PaymentTermAwareStub());
        $parent->expects($this->any())
            ->method('getName')
            ->willReturn('parent');

        $type = $this->createMock(FormInterface::class);
        $type->expects($this->any())
            ->method('getName')
            ->willReturn('entity');

        $resolvedType = $this->createMock(ResolvedFormTypeInterface::class);
        $resolvedType->expects($this->any())
            ->method('getInnerType')
            ->willReturn(new EntityTypeStub());

        $config = $this->createMock(FormConfigInterface::class);
        $config->expects($this->once())
            ->method('getOptions')
            ->willReturn([]);
        $config->expects($this->once())
            ->method('getType')
            ->willReturn($resolvedType);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getParent')
            ->willReturn($parent);
        $form->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);
        $form->expects($this->any())
            ->method('getName')
            ->willReturn('paymentTerm');

        $parent->expects($this->once())
            ->method('get')
            ->willReturn($form);
        $parent->expects($this->once())
            ->method('add')
            ->with(
                $this->logicalAnd($this->isType('string'), $this->equalTo('paymentTerm')),
                $this->logicalAnd($this->isType('string'), $this->equalTo(EntityTypeStub::class)),
                $this->logicalAnd($this->isType('array'), $this->equalTo($expected))
            );

        $formEvent = new FormEvent($form, new PaymentTermAwareStub());
        $builder = $this->createMock(FormBuilderInterface::class);

        $this->addCallbackAssert($builder, $formEvent);

        $this->extension->buildForm($builder, []);
    }

    public function parentDataProvider(): array
    {
        return [
            'empty customer group payment term' => [
                'customerPaymentTerm' => $this->getPaymentTerm(2),
                'customerGroupPaymentTerm' => null,
                'expected' => [
                    'attr' => [
                        'data-customer-payment-term' => 2,
                        'data-customer-group-payment-term' => null,
                    ],
                ],
            ],
            'empty customer payment term' => [
                'customerPaymentTerm' => null,
                'customerGroupPaymentTerm' => $this->getPaymentTerm(2),
                'expected' => [
                    'attr' => [
                        'data-customer-payment-term' => null,
                        'data-customer-group-payment-term' => 2,
                    ],
                ],
            ],
            'all payment terms available' => [
                'customerPaymentTerm' => $this->getPaymentTerm(5),
                'customerGroupPaymentTerm' => $this->getPaymentTerm(2),
                'expected' => [
                    'attr' => [
                        'data-customer-payment-term' => 5,
                        'data-customer-group-payment-term' => 2,
                    ],
                ],
            ],
        ];
    }
}
