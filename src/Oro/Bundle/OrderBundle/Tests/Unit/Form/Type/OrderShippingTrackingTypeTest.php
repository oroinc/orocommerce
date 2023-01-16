<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Oro\Bundle\OrderBundle\Form\Type\OrderShippingTrackingCollectionType;
use Oro\Bundle\OrderBundle\Form\Type\OrderShippingTrackingType;
use Oro\Bundle\OrderBundle\Form\Type\SelectSwitchInputType;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\TrackingAwareShippingMethodsProviderInterface;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

class OrderShippingTrackingTypeTest extends FormIntegrationTestCase
{
    /** @var TrackingAwareShippingMethodsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $trackingAwareShippingMethodsProvider;

    /** @var OrderShippingTrackingCollectionType */
    private $type;

    protected function setUp(): void
    {
        $this->trackingAwareShippingMethodsProvider = $this->createMock(
            TrackingAwareShippingMethodsProviderInterface::class
        );

        $this->type = new OrderShippingTrackingType($this->trackingAwareShippingMethodsProvider);

        parent::setUp();
    }

    private function getShippingMethod(string $identifier, string $label): ShippingMethodInterface
    {
        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $shippingMethod->expects(self::any())
            ->method('getIdentifier')
            ->willReturn($identifier);
        $shippingMethod->expects(self::any())
            ->method('getLabel')
            ->willReturn($label);

        return $shippingMethod;
    }

    public function testSubmitWhenNoTrackingAwareShippingMethods()
    {
        $submitted = ['method' => 'UPS', 'number' => '1Z111'];
        $expected = (new OrderShippingTracking())->setMethod('UPS')->setNumber('1Z111');

        $this->trackingAwareShippingMethodsProvider->expects(self::once())
            ->method('getTrackingAwareShippingMethods')
            ->willReturn([]);

        $form = $this->factory->create(OrderShippingTrackingType::class);
        $form->submit($submitted);

        self::assertTrue($form->isValid());
        self::assertEquals($expected, $form->getData());
    }

    public function testSubmit()
    {
        $submitted = ['method' => 'UPS', 'number' => '1Z111'];
        $expected = (new OrderShippingTracking())->setMethod('UPS')->setNumber('1Z111');

        $this->trackingAwareShippingMethodsProvider->expects(self::once())
            ->method('getTrackingAwareShippingMethods')
            ->willReturn([
                $this->getShippingMethod('UPS', 'UPS Shipping'),
                $this->getShippingMethod('Another', 'Another Shipping')
            ]);

        $form = $this->factory->create(OrderShippingTrackingType::class);
        $form->submit($submitted);

        self::assertTrue($form->isValid());
        self::assertEquals($expected, $form->getData());
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->type,
                    new TextType(),
                    new SelectSwitchInputType()
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testGetBlockPrefix()
    {
        self::assertSame('oro_order_shipping_tracking', $this->type->getBlockPrefix());
    }
}
