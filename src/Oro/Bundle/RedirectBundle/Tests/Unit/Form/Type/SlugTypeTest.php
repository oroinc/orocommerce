<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\RedirectBundle\Form\Type\SlugType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class SlugTypeTest extends FormIntegrationTestCase
{
    /**
     * @var SlugType
     */
    protected $formType;
    
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new SlugType();
    }

    public function testGetName()
    {
        $this->assertEquals(SlugType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(SlugType::NAME, $this->formType->getBlockPrefix());
    }
}
