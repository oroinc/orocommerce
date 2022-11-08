<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroHiddenNumberType;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Form\Type\OrderDiscountCollectionRowType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class OrderDiscountCollectionRowTypeTest extends FormIntegrationTestCase
{
    /** @var OrderDiscountCollectionRowType */
    private $type;

    protected function setUp(): void
    {
        $this->type = new OrderDiscountCollectionRowType();
        $this->type->setDataClass(OrderDiscount::class);
        parent::setUp();
    }

    public function testGetBlockPrefix()
    {
        self::assertSame(OrderDiscountCollectionRowType::NAME, $this->type->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        OrderDiscount $existing,
        array $submitted,
        OrderDiscount $expected
    ) {
        $options = [
            'data_class' => OrderDiscount::class
        ];

        $form = $this->factory->create(OrderDiscountCollectionRowType::class, $existing, $options);
        $form->submit($submitted);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($expected, $form->getData());
    }

    public function submitDataProvider(): array
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
    protected function getExtensions(): array
    {
        $numberFormatter = $this->createMock(NumberFormatter::class);

        return [
            new PreloadedExtension(
                [
                    $this->type,
                    OroHiddenNumberType::class => new OroHiddenNumberType($numberFormatter),
                ],
                []
            ),
            $this->getValidatorExtension(false)
        ];
    }

    public function testDefaultOptions()
    {
        $this->type->setDataClass(\stdClass::class);
        $form = $this->factory->create(OrderDiscountCollectionRowType::class);
        $this->assertSame(\stdClass::class, $form->getConfig()->getOptions()['data_class']);
    }
}
