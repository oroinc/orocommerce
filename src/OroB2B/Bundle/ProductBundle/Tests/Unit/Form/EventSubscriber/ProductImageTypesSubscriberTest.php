<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\EventSubscriber;

use Prophecy\Prophecy\ObjectProphecy;

use Oro\Bundle\LayoutBundle\Model\ThemeImageType;

use OroB2B\Bundle\ProductBundle\Entity\ProductImage;
use OroB2B\Bundle\ProductBundle\Form\EventSubscriber\ProductImageTypesSubscriber;

class ProductImageTypesSubscriberTest extends \PHPUnit_Framework_TestCase
{
    const TYPE_MAIN = 'main';
    const TYPE_LISTING = 'listing';
    const TYPE_ADDITIONAL = 'additional';

    /**
     * @var array
     */
    protected $typeLabels = [
        self::TYPE_MAIN => 'Main',
        self::TYPE_LISTING => 'Listing',
        self::TYPE_ADDITIONAL => 'Additional',
    ];

    /**
     * @var ProductImageTypesSubscriber
     */
    protected $productImageTypesSubscriber;

    public function setUp()
    {
        $this->productImageTypesSubscriber = new ProductImageTypesSubscriber([
            new ThemeImageType(self::TYPE_MAIN, $this->typeLabels[self::TYPE_MAIN], [], 1),
            new ThemeImageType(self::TYPE_ADDITIONAL, $this->typeLabels[self::TYPE_ADDITIONAL], [], null),
            new ThemeImageType(self::TYPE_LISTING, $this->typeLabels[self::TYPE_LISTING], [], 2)
        ]);
    }

    public function testPostSetData()
    {
        $productImage = new ProductImage();
        $productImage->addType(self::TYPE_MAIN);

        $form = $this->prophesize('Symfony\Component\Form\FormInterface');
        $form->getData()->willReturn($productImage);
        $this->addFormAddExpectation($form, self::TYPE_MAIN, 'radio', true);
        $this->addFormAddExpectation($form, self::TYPE_ADDITIONAL, 'checkbox', false);
        $this->addFormAddExpectation($form, self::TYPE_LISTING, 'checkbox', false);

        $event = $this->prophesize('Symfony\Component\Form\FormEvent');
        $event->getForm()->willReturn($form->reveal());

        $this->productImageTypesSubscriber->postSetData($event->reveal());
    }

    public function testPreSubmit()
    {
        $data = [
            'image' => 'some data',
            self::TYPE_MAIN => 1,
            self::TYPE_ADDITIONAL => 1
        ];

        $event = $this->prophesize('Symfony\Component\Form\FormEvent');
        $event->getData()->willReturn($data);
        $event
            ->setData(array_merge($data, ['types' => [self::TYPE_MAIN, self::TYPE_ADDITIONAL]]))
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
