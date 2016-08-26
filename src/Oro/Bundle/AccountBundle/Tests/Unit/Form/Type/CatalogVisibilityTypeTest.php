<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\AccountBundle\Form\Type\CatalogVisibilityType;

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
                CategoryVisibility::VISIBLE => 'oro.account.catalog.visibility.visible.label',
                CategoryVisibility::HIDDEN => 'oro.account.catalog.visibility.hidden.label',
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
