<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductStatusType;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Provider\ProductUnitsProvider;

class ProductUnitsTypeTest extends FormIntegrationTestCase
{
    /** @var  ProductUnitsType $productUnitsType */
    protected $productUnitsType;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProductUnitsProvider $productUnitsProvider */
    protected $productUnitsProvider;

    public function setup()
    {
        parent::setUp();
        $this->productUnitsProvider =
            $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Provider\ProductUnitsProvider')
                ->disableOriginalConstructor()
                ->getMock();

        $this->productUnitsProvider
            ->method('getAvailableProductUnits')
            ->willReturn([
                'each' => 'each',
                'kg' => 'kilogram'
            ]);

        $this->productUnitsType = new ProductUnitsType($this->productUnitsProvider);
    }

    public function testGetName()
    {
        $this->assertEquals(ProductStatusType::NAME, $this->productUnitsType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->productUnitsType->getParent());
    }

    public function testChoices()
    {
        $form = $this->factory->create($this->productUnitsType);

        $this->assertEquals(
            $this->productUnitsProvider->getAvailableProductUnits(),
            $form->getConfig()->getOptions()['choices']
        );
    }
}
