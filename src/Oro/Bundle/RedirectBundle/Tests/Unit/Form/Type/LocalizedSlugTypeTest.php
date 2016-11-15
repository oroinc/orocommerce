<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class LocalizedSlugTypeTest extends FormIntegrationTestCase
{
    /**
     * @var LocalizedSlugType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->formType = new LocalizedSlugType($registry);
    }

    public function testGetName()
    {
        $this->assertEquals(LocalizedSlugType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(LocalizedSlugType::NAME, $this->formType->getBlockPrefix());
    }
}
