<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method\View\Factory;

use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\View\Factory\PaymentTermPaymentMethodViewFactory;
use Oro\Bundle\PaymentTermBundle\Method\View\PaymentTermView;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class PaymentTermPaymentMethodViewFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentTermProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $paymentTermProvider;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var PaymentTermPaymentMethodViewFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->paymentTermProvider = $this->createMock(PaymentTermProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->factory = new PaymentTermPaymentMethodViewFactory(
            $this->paymentTermProvider,
            $this->translator
        );
    }

    public function testCreate()
    {
        /** @var PaymentTermConfigInterface|\PHPUnit\Framework\MockObject\MockObject $config */
        $config = $this->createMock(PaymentTermConfigInterface::class);

        $view = new PaymentTermView(
            $this->paymentTermProvider,
            $this->translator,
            $config
        );

        static::assertEquals($view, $this->factory->create($config));
    }
}
