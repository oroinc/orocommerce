<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductOfferCollectionType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductOfferType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteProductOfferCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var QuoteProductOfferCollectionType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->formType = new QuoteProductOfferCollectionType();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'entry_type' => QuoteProductOfferType::class,
                'show_form_when_empty' => false,
                'error_bubbling' => false,
                'prototype_name' => '__namequoteproductoffer__',
            ])
        ;

        $this->formType->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(CollectionType::class, $this->formType->getParent());
    }
}
