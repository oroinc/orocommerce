<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\RedirectBundle\Form\Type\SlugifyLocalizedFieldIntoSlugType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class SlugifyLocalizedFieldIntoSlugTypeTest extends FormIntegrationTestCase
{
    /**
     * @var SlugifyLocalizedFieldIntoSlugType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->formType = new SlugifyLocalizedFieldIntoSlugType($registry);
    }

    public function testInheritorOfLocalizedFallbackValueCollectionType()
    {
        $this->assertInstanceOf(
            'Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType',
            $this->formType
        );
    }

    public function testInheritorOfSlugifyFieldIntoSlugTrait()
    {
        $this->assertArrayHasKey(
            'Oro\Bundle\RedirectBundle\Form\Type\SlugifyFieldIntoSlugTrait',
            class_uses($this->formType)
        );
    }

    public function testGetName()
    {
        $this->assertEquals(SlugifyLocalizedFieldIntoSlugType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(SlugifyLocalizedFieldIntoSlugType::NAME, $this->formType->getBlockPrefix());
    }

    public function testGetComponent()
    {
        $this->assertEquals(SlugifyLocalizedFieldIntoSlugType::COMPONENT, $this->formType->getComponent());
    }
}
