<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitPrecisionCollectionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitPrecisionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductUnitPrecisionCollectionTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductUnitPrecisionCollectionType */
    private $formType;

    protected function setUp(): void
    {
        $this->formType = new ProductUnitPrecisionCollectionType();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'entry_type' => ProductUnitPrecisionType::class,
                'show_form_when_empty' => false
            ]);

        $this->formType->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(CollectionType::class, $this->formType->getParent());
    }
}
