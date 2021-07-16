<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\CMSBundle\Form\Type\LoginPageType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ImageTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class LoginPageTypeTest extends FormIntegrationTestCase
{
    /**
     * @var LoginPageType
     */
    private $formType;

    protected function setUp(): void
    {
        $this->formType = new LoginPageType();
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    ImageType::class => new ImageTypeStub(),
                ],
                []
            )
        ];
    }

    public function testBuildFormWithCssField(): void
    {
        $this->formType = new LoginPageType(true);
        $form = $this->buildForm();
        self::assertTrue($form->has('css'));
    }

    public function testBuildFormWithoutCssField(): void
    {
        $this->formType = new LoginPageType(false);
        $form = $this->buildForm();
        self::assertFalse($form->has('css'));
    }

    private function buildForm(): FormInterface
    {
        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->getFormFactory();

        $form = $this->factory->create(LoginPageType::class);

        self::assertTrue($form->has('topContent'));
        self::assertTrue($form->has('bottomContent'));
        self::assertTrue($form->has('logoImage'));
        self::assertTrue($form->has('backgroundImage'));

        return $form;
    }
}
