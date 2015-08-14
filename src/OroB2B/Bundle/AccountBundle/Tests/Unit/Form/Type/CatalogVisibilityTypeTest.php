<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\AccountBundle\Form\Type\CatalogVisibilityType;

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
                CatalogVisibilityType::VISIBILITY_VISIBLE => 'orob2b.account.catalog.visibility.visible.label',
                CatalogVisibilityType::VISIBILITY_HIDDEN => 'orob2b.account.catalog.visibility.hidden.label',
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
