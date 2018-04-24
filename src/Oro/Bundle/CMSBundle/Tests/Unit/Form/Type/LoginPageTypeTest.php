<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\CMSBundle\Form\Type\LoginPageType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ImageTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class LoginPageTypeTest extends FormIntegrationTestCase
{
    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    ImageType::class => new ImageTypeStub(),
                ],
                []
            )
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(LoginPageType::class);

        $this->assertTrue($form->has('topContent'));
        $this->assertTrue($form->has('bottomContent'));
        $this->assertTrue($form->has('css'));
        $this->assertTrue($form->has('logoImage'));
        $this->assertTrue($form->has('backgroundImage'));
    }
}
