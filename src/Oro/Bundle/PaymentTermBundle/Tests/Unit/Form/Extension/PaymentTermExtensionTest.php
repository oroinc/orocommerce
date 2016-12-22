<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Form\Extension\PaymentTermExtension;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Oro\Bundle\PaymentTermBundle\Tests\Unit\PaymentTermAwareStub;
use Oro\Component\Testing\Unit\EntityTrait;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;

class PaymentTermExtensionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var PaymentTermExtension */
    protected $extension;

    /** @var PaymentTermProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTermProvider;

    protected function setUp()
    {
        $this->paymentTermProvider = $this->getMockBuilder(PaymentTermProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new PaymentTermExtension($this->paymentTermProvider);
    }

    public function testGetExtended()
    {
        $this->assertSame('oro_payment_term_select', $this->extension->getExtendedType());
    }

    /**
     * @param FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder
     * @param FormEvent $formEvent
     */
    private function addCallbackAssert(FormBuilderInterface $builder, FormEvent $formEvent)
    {
        $builder->expects($this->once())->method('addEventListener')->with(
            $this->logicalAnd(
                $this->isType('string'),
                $this->equalTo('form.post_set_data')
            ),
            $this->logicalAnd(
                $this->isInstanceOf(\Closure::class),
                $this->callback(
                    function (\Closure $closure) use ($formEvent) {
                        $closure($formEvent);

                        return true;
                    }
                )
            )
        );
    }

    public function testBuildWithoutParent()
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('getParent')->willReturn(null);
        $formEvent = new FormEvent($form, new PaymentTermAwareStub());
        $builder = $this->createMock(FormBuilderInterface::class);

        $this->addCallbackAssert($builder, $formEvent);

        $this->extension->buildForm($builder, []);
    }

    public function testBuildWithoutParentData()
    {
        $parent = $this->createMock(FormInterface::class);
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('getParent')->willReturn($parent);
        $parent->expects($this->once())->method('getData')->willReturn(null);
        $formEvent = new FormEvent($form, new PaymentTermAwareStub());
        $builder = $this->createMock(FormBuilderInterface::class);

        $this->addCallbackAssert($builder, $formEvent);

        $this->extension->buildForm($builder, []);
    }

    public function testBuildParentDataNotAccountOwnerAwareInterface()
    {
        $parent = $this->createMock(FormInterface::class);
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('getParent')->willReturn($parent);
        $parent->expects($this->once())->method('getData')->willReturn(new \stdClass());
        $formEvent = new FormEvent($form, new PaymentTermAwareStub());
        $builder = $this->createMock(FormBuilderInterface::class);

        $this->addCallbackAssert($builder, $formEvent);

        $this->extension->buildForm($builder, []);
    }

    /**
     * @dataProvider parentDataProvider
     * @param PaymentTerm $accountPaymentTerm
     * @param PaymentTerm $accountGroupPaymentTerm
     * @param array $expected
     */
    public function testBuildParentDataReplacePaymentTermAttributes(
        PaymentTerm $accountPaymentTerm = null,
        PaymentTerm $accountGroupPaymentTerm = null,
        array $expected
    ) {
        $this->paymentTermProvider->expects($this->once())->method('getAccountPaymentTermByOwner')
            ->willReturn($accountPaymentTerm);
        $this->paymentTermProvider->expects($this->once())->method('getAccountGroupPaymentTermByOwner')
            ->willReturn($accountGroupPaymentTerm);

        $parent = $this->createMock(FormInterface::class);
        $parent->expects($this->once())->method('getData')->willReturn(new PaymentTermAwareStub());
        $parent->expects($this->any())->method('getName')->willReturn('parent');

        $type = $this->createMock(ResolvedFormTypeInterface::class);
        $type->expects($this->any())->method('getName')->willReturn('entity');

        $config = $this->createMock(FormConfigInterface::class);
        $config->expects($this->once())->method('getOptions')->willReturn([]);
        $config->expects($this->once())->method('getType')->willReturn($type);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('getParent')->willReturn($parent);
        $form->expects($this->once())->method('getConfig')->willReturn($config);
        $form->expects($this->any())->method('getName')->willReturn('paymentTerm');

        $parent->expects($this->once())->method('get')->willReturn($form);
        $parent->expects($this->once())->method('add')->with(
            $this->logicalAnd(
                $this->isType('string'),
                $this->equalTo('paymentTerm')
            ),
            $this->logicalAnd(
                $this->isType('string'),
                $this->equalTo('entity')
            ),
            $this->logicalAnd(
                $this->isType('array'),
                $this->equalTo($expected)
            )
        );

        $formEvent = new FormEvent($form, new PaymentTermAwareStub());
        $builder = $this->createMock(FormBuilderInterface::class);

        $this->addCallbackAssert($builder, $formEvent);

        $this->extension->buildForm($builder, []);
    }

    /**
     * @return array
     */
    public function parentDataProvider()
    {
        return [
            'empty account group payment term' => [
                'accountPaymentTerm' => $this->getEntity(PaymentTerm::class, ['id' => 2]),
                'accountGroupPaymentTerm' => null,
                'expected' => [
                    'attr' => [
                        'data-account-payment-term' => 2,
                        'data-account-group-payment-term' => null,
                    ],
                ],
            ],
            'empty account payment term' => [
                'accountPaymentTerm' => null,
                'accountGroupPaymentTerm' => $this->getEntity(PaymentTerm::class, ['id' => 2]),
                'expected' => [
                    'attr' => [
                        'data-account-payment-term' => null,
                        'data-account-group-payment-term' => 2,
                    ],
                ],
            ],
            'all payment terms available' => [
                'accountPaymentTerm' => $this->getEntity(PaymentTerm::class, ['id' => 5]),
                'accountGroupPaymentTerm' => $this->getEntity(PaymentTerm::class, ['id' => 2]),
                'expected' => [
                    'attr' => [
                        'data-account-payment-term' => 5,
                        'data-account-group-payment-term' => 2,
                    ],
                ],
            ],
        ];
    }
}
