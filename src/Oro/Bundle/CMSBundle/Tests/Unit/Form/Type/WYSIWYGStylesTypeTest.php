<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGStylesType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class WYSIWYGStylesTypeTest extends FormIntegrationTestCase
{
    public function testGetParent()
    {
        $type = new WYSIWYGStylesType();
        $this->assertEquals(HiddenType::class, $type->getParent());
    }

    public function testSubmit()
    {
        $form = $this->factory->create(WYSIWYGStylesType::class);
        $form->submit('h1 { color: black; }');
        $this->assertEquals('h1 { color: black; }', $form->getData());
    }
}
