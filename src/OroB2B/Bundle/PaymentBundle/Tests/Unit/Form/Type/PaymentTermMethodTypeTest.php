<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\PaymentBundle\Form\Type\PaymentTermMethodType;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class PaymentTermMethodTypeTest extends FormIntegrationTestCase
{
    const PAYMENT_TERM_LABEL = 'test_payment_term';

    /**
     * @var PaymentTermMethodType
     */
    protected $formType;

    /**
     * @var PaymentTermProvider
     */
    protected $paymentTermProvider;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        /** @var PaymentTermProvider|\PHPUnit_Framework_MockObject_MockObject $paymentTermProvider */
        $this->paymentTermProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject $tokenStorage */
        $this->tokenStorage = $this
            ->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new PaymentTermMethodType($this->paymentTermProvider, $this->tokenStorage);
    }

    public function testFormConfiguration()
    {
        $form = $this->factory->create($this->formType);
        $this->assertTrue($form->getConfig()->hasOption('label'));

        $label = $form->getConfig()->getOption('label');
        $this->assertEquals('orob2b.payment.methods.term_method.label', $label);
    }

    public function testGetName()
    {
        $this->assertEquals(PaymentTermMethodType::NAME, $this->formType->getName());
    }

    public function testFinishViewNoToken()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $formView = new FormView();

        $this->formType->finishView($formView, $form, []);

        $this->assertFalse($formView->vars['method_enabled']);
        $this->assertEquals($formView->vars['payment_term'], '');
    }

    public function testFinishViewNoUser()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $formView = new FormView();

        /** @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->formType->finishView($formView, $form, []);

        $this->assertFalse($formView->vars['method_enabled']);
        $this->assertEquals($formView->vars['payment_term'], '');
    }

    public function testFinishViewWrongUser()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $formView = new FormView();

        /** @var User|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');

        /** @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->formType->finishView($formView, $form, []);

        $this->assertFalse($formView->vars['method_enabled']);
        $this->assertEquals($formView->vars['payment_term'], '');
    }

    public function testFinishViewOk()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $formView = new FormView();

        /** @var Account|\PHPUnit_Framework_MockObject_MockObject $user */
        $account = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\Account');

        /** @var AccountUser|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountUser');
        $user->expects($this->once())
            ->method('getAccount')
            ->willReturn($account);

        /** @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        /** @var PaymentTerm|\PHPUnit_Framework_MockObject_MockObject $paymentTerm */
        $paymentTerm = $this->getMock('OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm');
        $paymentTerm->expects($this->once())
            ->method('getLabel')
            ->willReturn(self::PAYMENT_TERM_LABEL);

        $this->paymentTermProvider->expects($this->once())
            ->method('getPaymentTerm')
            ->willReturn($paymentTerm);

        $this->formType->finishView($formView, $form, []);

        $this->assertTrue($formView->vars['method_enabled']);
        $this->assertEquals($formView->vars['payment_term'], self::PAYMENT_TERM_LABEL);
    }
}
