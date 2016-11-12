<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\RedirectBundle\Form\Type\SlugifyTextFieldIntoSlugType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class SlugifyTextFieldIntoSlugTypeTest extends FormIntegrationTestCase
{
    /**
     * @var SlugifyTextFieldIntoSlugType
     */
    protected $formType;
    
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new SlugifyTextFieldIntoSlugType();
    }

    public function testInheritorOfTextType()
    {
        $this->assertInstanceOf(
            'Symfony\Component\Form\Extension\Core\Type\TextType',
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
        $this->assertEquals(SlugifyTextFieldIntoSlugType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(SlugifyTextFieldIntoSlugType::NAME, $this->formType->getBlockPrefix());
    }

    public function testGetComponent()
    {
        $this->assertEquals(SlugifyTextFieldIntoSlugType::COMPONENT, $this->formType->getComponent());
    }
}
