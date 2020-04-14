<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\Type;

use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Form\Type\CatalogVisibilityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CatalogVisibilityTypeTest extends FormIntegrationTestCase
{
    /**
     * @var CatalogVisibilityType
     */
    protected $type;

    protected function setUp(): void
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
                'oro.visibility.catalog.visibility.visible.label' => CategoryVisibility::VISIBLE,
                'oro.visibility.catalog.visibility.hidden.label' => CategoryVisibility::HIDDEN,
            ],
            $resolvedOptions['choices']
        );
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->type->getParent());
    }
}
