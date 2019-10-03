<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class WYSIWYGTypeTest extends FormIntegrationTestCase
{
    public function testGetParent()
    {
        $type = new WYSIWYGType();
        $this->assertEquals(TextareaType::class, $type->getParent());
    }

    public function testSubmit()
    {
        $form = $this->factory->create(WYSIWYGType::class);
        $form->submit('<h1>Heading text</h1><p>Body text</p>');
        $this->assertEquals('<h1>Heading text</h1><p>Body text</p>', $form->getData());
    }
}
