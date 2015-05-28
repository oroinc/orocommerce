<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\PricingBundle\Form\Type\ProductPriceCollectionType;
use OroB2B\Bundle\PricingBundle\Form\Type\ProductPriceType;

class ProductPriceCollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductPriceCollectionType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new ProductPriceCollectionType('\stdClass');
    }

    protected function tearDown()
    {
        unset($this->formType);
    }

    public function testGetParent()
    {
        $this->assertInternalType('string', $this->formType->getParent());
        $this->assertEquals(CollectionType::NAME, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->formType->getName());
        $this->assertEquals(ProductPriceCollectionType::NAME, $this->formType->getName());
    }

    public function testSetDefaultOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                $this->callback(
                    function (array $options) {
                        $this->assertArrayHasKey('type', $options);
                        $this->assertEquals(ProductPriceType::NAME, $options['type']);

                        $this->assertArrayHasKey('show_form_when_empty', $options);
                        $this->assertEquals(false, $options['show_form_when_empty']);

                        $this->assertArrayHasKey('options', $options);
                        $this->assertNotEmpty($options['options']);
                        $this->assertArrayHasKey('data_class', $options['options']);

                        return true;
                    }
                )
            );

        $this->formType->setDefaultOptions($resolver);
    }
}
