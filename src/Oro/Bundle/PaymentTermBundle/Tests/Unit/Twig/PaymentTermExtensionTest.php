<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Twig;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Oro\Bundle\PaymentTermBundle\Twig\PaymentTermExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class PaymentTermExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var PaymentTermExtension */
    protected $extension;

    /** @var PaymentTermProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $dataProvider;

    protected function setUp(): void
    {
        $this->dataProvider = $this->createMock(PaymentTermProvider::class);

        $container = self::getContainerBuilder()
            ->add('oro_payment_term.provider.payment_term', $this->dataProvider)
            ->getContainer($this);

        $this->extension = new PaymentTermExtension($container);
    }

    public function testGetPaymentTerm()
    {
        $object = new \stdClass();
        $paymentTerm = new PaymentTerm();

        $this->dataProvider->expects($this->once())
            ->method('getObjectPaymentTerm')
            ->with($object)
            ->willReturn($paymentTerm);

        $this->assertEquals(
            $paymentTerm,
            self::callTwigFunction($this->extension, 'get_payment_term', [$object])
        );
    }
}
