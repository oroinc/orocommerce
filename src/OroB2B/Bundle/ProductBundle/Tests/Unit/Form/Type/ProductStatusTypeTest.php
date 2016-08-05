<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductStatusType;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Provider\ProductStatusProvider;

class ProductStatusTypeTest extends FormIntegrationTestCase
{
    /** @var  ProductStatusType $productStatusType */
    protected $productStatusType;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProductStatusProvider $productStatusProvider */
    protected $productStatusProvider;

    public function setup()
    {
        parent::setUp();
        $this->productStatusProvider =
            $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Provider\ProductStatusProvider')
                ->disableOriginalConstructor()
                ->getMock();

        $this->productStatusProvider
            ->method('getAvailableProductStatuses')
            ->willReturn([
                Product::STATUS_DISABLED => 'Disabled',
                Product::STATUS_ENABLED => 'Enabled'
            ]);

        $this->productStatusType = new ProductStatusType($this->productStatusProvider);
    }

    public function testGetName()
    {
        $this->assertEquals(ProductStatusType::NAME, $this->productStatusType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->productStatusType->getParent());
    }

    public function testChoices()
    {
        $form = $this->factory->create($this->productStatusType);
        $availableProductStatuses = $this->productStatusProvider->getAvailableProductStatuses();
        $choices = [];

        foreach ($availableProductStatuses as $key => $value) {
            $choices[] = new ChoiceView($key, $key, $value);
        }

        $this->assertEquals(
            $choices,
            $form->createView()->vars['choices']
        );

        $this->assertEquals(
            Product::STATUS_DISABLED,
            $form->getConfig()->getOptions()['preferred_choices']
        );
    }
}
