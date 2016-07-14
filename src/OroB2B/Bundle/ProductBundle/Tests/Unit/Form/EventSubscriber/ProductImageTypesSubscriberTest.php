<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\EventSubscriber;

use Prophecy\Prophecy\ObjectProphecy;

use Oro\Bundle\LayoutBundle\Model\ThemeImageType;

use OroB2B\Bundle\ProductBundle\Entity\ProductImage;
use OroB2B\Bundle\ProductBundle\Entity\ProductImageType;
use OroB2B\Bundle\ProductBundle\Form\EventSubscriber\ProductImageTypesSubscriber;

class ProductImageTypesSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $typeLabels = [
        ProductImageType::TYPE_MAIN => 'Main',
        ProductImageType::TYPE_LISTING => 'Listing',
        ProductImageType::TYPE_ADDITIONAL => 'Additional',
    ];

    /**
     * @var ProductImageTypesSubscriber
     */
    protected $productImageTypesSubscriber;

    public function setUp()
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

        $form = $this->prophesize('Symfony\Component\Form\FormInterface');
        $form->getData()->willReturn($productImage);
        $this->addFormAddExpectation($form, ProductImageType::TYPE_MAIN, 'radio', true);
        $this->addFormAddExpectation($form, ProductImageType::TYPE_ADDITIONAL, 'checkbox', false);
        $this->addFormAddExpectation($form, ProductImageType::TYPE_LISTING, 'checkbox', false);

        $event = $this->prophesize('Symfony\Component\Form\FormEvent');
        $event->getForm()->willReturn($form->reveal());

        $this->productImageTypesSubscriber->postSetData($event->reveal());
    }

    public function testPreSubmit()
    {
        $data = [
            'image' => 'some data',
            ProductImageType::TYPE_MAIN => 1,
            ProductImageType::TYPE_ADDITIONAL => 1
        ];

        $event = $this->prophesize('Symfony\Component\Form\FormEvent');
        $event->getData()->willReturn($data);
        $event
            ->setData(array_merge($data, ['types' => [ProductImageType::TYPE_MAIN, ProductImageType::TYPE_ADDITIONAL]]))
            ->shouldBeCalled();

        $this->productImageTypesSubscriber->preSubmit($event->reveal());
    }

    /**
     * @param ObjectProphecy $form
     * @param string $name
     * @param string $type
     * @param bool $data
     */
    private function addFormAddExpectation(ObjectProphecy $form, $name, $type, $data)
    {
        $form->add($name, $type, [
            'label' => $this->typeLabels[$name],
            'value' => 1,
            'mapped' => false,
            'data' => $data
        ])->shouldBeCalled();
    }
}
