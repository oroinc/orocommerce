<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductRequestCollectionType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductRequestType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

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

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'entry_type' => QuoteProductRequestType::NAME,
                'show_form_when_empty' => false,
                'prototype_name' => '__namequoteproductrequest__',
                'allow_add' => false,
                'allow_delete' => false,
            ])
        ;

        $this->formType->configureOptions($resolver);
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
