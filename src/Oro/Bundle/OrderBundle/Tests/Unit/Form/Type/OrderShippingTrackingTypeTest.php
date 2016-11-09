<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Oro\Bundle\OrderBundle\Form\Type\OrderShippingTrackingType;
use Oro\Bundle\OrderBundle\Form\Type\SelectSwitchInputType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;

class OrderShippingTrackingTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var OrderShippingTrackingType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();
        $this->type = new OrderShippingTrackingType();
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $submitted
     * @param OrderShippingTracking $expected
     */
    public function testSubmit(array $submitted, OrderShippingTracking $expected)
    {
        $form = $this->factory->create($this->type);
        $form->submit($submitted);

        static::assertTrue($form->isValid());
        static::assertEquals($expected, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'test' => [
                'submitted' => [
                        'method' => 'UPS',
                        'number' => '1Z111',

                ],
                'expected' => (new OrderShippingTracking())->setMethod('UPS')->setNumber('1Z111'),
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    TextType::class => new TextType(),
                    SelectSwitchInputType::class => new SelectSwitchInputType()
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testGetName()
    {
        static::assertSame(OrderShippingTrackingType::NAME, $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        static::assertSame(OrderShippingTrackingType::NAME, $this->type->getBlockPrefix());
    }
}
