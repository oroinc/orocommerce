<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\PayPalBundle\Form\Type\CreditCardType;
use Oro\Bundle\PayPalBundle\Layout\DataProvider\CreditCardFormProvider;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CreditCardFormProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FormFactoryInterface| \PHPUnit\Framework\MockObject\MockObject
     */
    protected $formFactory;

    /**
     * @var CreditCardFormProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|UrlGeneratorInterface
     */
    protected $router;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock('Symfony\Component\Form\FormFactoryInterface');
        $this->router = $this->createMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');

        $this->provider = new CreditCardFormProvider($this->formFactory, $this->router);
    }

    public function testGetCreditCardFormView()
    {
        $formView = $this->createMock(FormView::class);

        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreditCardType::class, null, [])
            ->willReturn($form);

        $creditCardForm = $this->provider->getCreditCardFormView();
        $this->assertInstanceOf(FormView::class, $creditCardForm);
    }
}
