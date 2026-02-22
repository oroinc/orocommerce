<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Twig;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Oro\Bundle\PaymentTermBundle\Twig\PaymentTermExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentTermExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private PaymentTermProvider&MockObject $dataProvider;
    private PaymentTermExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->dataProvider = $this->createMock(PaymentTermProvider::class);

        $container = self::getContainerBuilder()
            ->add('oro_payment_term.provider.payment_term', $this->dataProvider)
            ->getContainer($this);

        $this->extension = new PaymentTermExtension($container);
    }

    public function testGetPaymentTerm(): void
    {
        $object = new \stdClass();
        $paymentTerm = new PaymentTerm();

        $this->dataProvider->expects(self::once())
            ->method('getObjectPaymentTerm')
            ->with($object)
            ->willReturn($paymentTerm);

        self::assertEquals(
            $paymentTerm,
            self::callTwigFunction($this->extension, 'get_payment_term', [$object])
        );
    }
}
