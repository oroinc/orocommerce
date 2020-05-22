<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Form\EventSubscriber\ProductImageTypesSubscriber;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class ProductImageTypesSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var array */
    protected $typeLabels = [
        ProductImageType::TYPE_MAIN => 'Main',
        ProductImageType::TYPE_LISTING => 'Listing',
        ProductImageType::TYPE_ADDITIONAL => 'Additional',
    ];

    /** @var ProductImageTypesSubscriber */
    protected $productImageTypesSubscriber;

    protected function setUp(): void
    {
        $this->productImageTypesSubscriber = new ProductImageTypesSubscriber([
            new ThemeImageType(
                ProductImageType::TYPE_MAIN,
                $this->typeLabels[ProductImageType::TYPE_MAIN],
                [],
                1
            ),
            new ThemeImageType(
                ProductImageType::TYPE_ADDITIONAL,
                $this->typeLabels[ProductImageType::TYPE_ADDITIONAL],
                [],
                null
            ),
            new ThemeImageType(
                ProductImageType::TYPE_LISTING,
                $this->typeLabels[ProductImageType::TYPE_LISTING],
                [],
                2
            )
        ]);
    }

    public function testPostSetData()
    {
        $productImage = new ProductImage();
        $productImage->addType(ProductImageType::TYPE_MAIN);

        $form = $this->createMock(FormInterface::class);
        $form->method('getData')->willReturn($productImage);
        $form->expects(static::exactly(3))
            ->method('add')
            ->withConsecutive(
                [
                    ProductImageType::TYPE_MAIN,
                    RadioType::class,
                    [
                        'label' => $this->typeLabels[ProductImageType::TYPE_MAIN],
                        'value' => 1,
                        'mapped' => false,
                        'data' => true
                    ]
                ],
                [
                    ProductImageType::TYPE_ADDITIONAL,
                    CheckboxType::class,
                    [
                        'label' => $this->typeLabels[ProductImageType::TYPE_ADDITIONAL],
                        'value' => 1,
                        'mapped' => false,
                        'data' => false
                    ]
                ],
                [ProductImageType::TYPE_LISTING,
                    CheckboxType::class,
                    [
                        'label' => $this->typeLabels[ProductImageType::TYPE_LISTING],
                        'value' => 1,
                        'mapped' => false,
                        'data' => false
                    ]
                ]
            );

        $event = new FormEvent($form, null);

        $this->productImageTypesSubscriber->postSetData($event);
    }

    public function testPreSubmit()
    {
        $data = [
            'image' => 'some data',
            ProductImageType::TYPE_MAIN => 1,
            ProductImageType::TYPE_ADDITIONAL => 1
        ];

        $event = new FormEvent($this->createMock(FormInterface::class), $data);

        $this->productImageTypesSubscriber->preSubmit($event);

        static::assertEquals(
            \array_merge($data, ['types' => [ProductImageType::TYPE_MAIN, ProductImageType::TYPE_ADDITIONAL]]),
            $event->getData()
        );
    }
}
