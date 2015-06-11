<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductItemType;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductItemCollectionType;

class QuoteProductItemCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var QuoteProductItemCollectionType
     */
    protected $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->type = new QuoteProductItemCollectionType();
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'type'  => QuoteProductItemType::NAME,
                'show_form_when_empty' => false,
                'prototype_name'       => '__namequoteproductitem__',
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
        $this->assertEquals(QuoteProductItemCollectionType::NAME, $this->type->getName());
    }
}
