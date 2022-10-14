<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\PayPalBundle\Form\Type\CreditCardType;
use Oro\Bundle\PayPalBundle\Layout\DataProvider\CreditCardFormProvider;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CreditCardFormProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface| \PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|UrlGeneratorInterface */
    private $router;

    /** @var CreditCardFormProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->router = $this->createMock(UrlGeneratorInterface::class);

        $this->provider = new CreditCardFormProvider($this->formFactory, $this->router);
    }

    public function testGetCreditCardFormView()
    {
        $formView = $this->createMock(FormView::class);

        $form = $this->createMock(FormInterface::class);
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
