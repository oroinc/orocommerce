<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Form\Type\CatalogVisibilityType;

class CatalogVisibilityTypeTest extends FormIntegrationTestCase
{
    /**
     * @var CatalogVisibilityType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new CatalogVisibilityType();
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->type->configureOptions($resolver);
        $resolvedOptions = $resolver->resolve();

        $this->assertEquals(
            [
                CategoryVisibility::VISIBLE => 'oro.visibility.catalog.visibility.visible.label',
                CategoryVisibility::HIDDEN => 'oro.visibility.catalog.visibility.hidden.label',
            ],
            $resolvedOptions['choices']
        );
    }

    public function testGetName()
    {
        $this->assertEquals(CatalogVisibilityType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->type->getParent());
    }
}
