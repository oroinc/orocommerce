<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductType;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductCollectionType;

class QuoteProductCollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QuoteProductCollectionType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new QuoteProductCollectionType();
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'type'  => QuoteProductType::NAME,
                'show_form_when_empty' => false,
                'prototype_name'       => '__namequoteproduct__',
            ])
        ;

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_collection', $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(QuoteProductCollectionType::NAME, $this->type->getName());
    }
}
