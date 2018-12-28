<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroHiddenNumberType;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Form\Type\OrderDiscountCollectionRowType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;

class OrderDiscountCollectionRowTypeTest extends FormIntegrationTestCase
{
    /**
     * @var OrderDiscountCollectionRowType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new OrderDiscountCollectionRowType();
        $this->type->setDataClass(OrderDiscount::class);
    }

    public function testGetName()
    {
        static::assertSame(OrderDiscountCollectionRowType::NAME, $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        static::assertSame(OrderDiscountCollectionRowType::NAME, $this->type->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param OrderDiscount $existing
     * @param array $submitted
     * @param OrderDiscount $expected
     */
    public function testSubmit(
        OrderDiscount $existing,
        array $submitted,
        OrderDiscount $expected = null
    ) {
        $options = [
            'data_class' => OrderDiscount::class
        ];

        $form = $this->factory->create($this->type, $existing, $options);
        $form->submit($submitted);

        static::assertTrue($form->isValid());
        if ($form->isValid()) {
            static::assertEquals($expected, $form->getData());
        }
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'amount discount' => [
                'existing' => new OrderDiscount(),
                'submitted' => [
                    'type' => OrderDiscount::TYPE_AMOUNT,
                    'description' => 'anything',
                    'percent' => '',
                    'amount' => '123',
                ],
                'expected' => (new OrderDiscount())
                    ->setType(OrderDiscount::TYPE_AMOUNT)->setDescription('anything')->setAmount(123)
            ],
            'update amount discount to percent' => [
                'existing' => (new OrderDiscount())
                    ->setType(OrderDiscount::TYPE_AMOUNT)->setDescription('anything')->setAmount(123),
                'submitted' => [
                    'type' => OrderDiscount::TYPE_PERCENT,
                    'description' => '',
                    'percent' => '20',
                    'amount' => '',
                ],
                'expected' => (new OrderDiscount())
                    ->setType(OrderDiscount::TYPE_PERCENT)->setPercent(20)
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $numberFormatter = $this->createMock(NumberFormatter::class);

        return [
            $this->getValidatorExtension(false),
            new PreloadedExtension(
                [
                    OroHiddenNumberType::class => new OroHiddenNumberType($numberFormatter),
                ],
                []
            ),
        ];
    }

    public function testDefaultOptions()
    {
        $this->type->setDataClass(\stdClass::class);
        $form = $this->factory->create($this->type);
        static::assertArraySubset(['data_class' => \stdClass::class], $form->getConfig()->getOptions());
    }
}
