<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Form\Type\ProductVisibilityType;

class PruductVisibilityTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductVisibilityType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new ProductVisibilityType();
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->type->configureOptions($resolver);
        $resolvedOptions = $resolver->resolve();

        $this->assertEquals(
            [
                ProductVisibility::VISIBLE => 'oro.visibility.product.visibility.visible.label',
                ProductVisibility::HIDDEN => 'oro.visibility.product.visibility.hidden.label',
            ],
            $resolvedOptions['choices']
        );
    }

    public function testGetName()
    {
        $this->assertEquals(ProductVisibilityType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->type->getParent());
    }
}
