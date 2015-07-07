<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductRequestType;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductRequestCollectionType;

class QuoteProductRequestCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var QuoteProductRequestCollectionType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new QuoteProductRequestCollectionType();
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'type' => QuoteProductRequestType::NAME,
                'show_form_when_empty' => false,
                'prototype_name' => '__namequoteproductrequest__',
                       'allow_add' => false,
                'allow_delete' => false,
            ])
        ;

            $this->formType->setDefaultOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(CollectionType::NAME, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(QuoteProductRequestCollectionType::NAME, $this->formType->getName());
    }
}
