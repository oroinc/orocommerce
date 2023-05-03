<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type;

use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;
use Oro\Bundle\RedirectBundle\Form\Type\SlugType;
use Oro\Bundle\RedirectBundle\Form\Type\SlugWithRedirectType;
use Oro\Bundle\RedirectBundle\Helper\ConfirmSlugChangeFormHelper;
use Oro\Bundle\RedirectBundle\Helper\SlugifyFormHelper;
use Oro\Bundle\RedirectBundle\Model\TextSlugPrototypeWithRedirect;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SlugWithRedirectTypeTest extends FormIntegrationTestCase
{
    /** @var ConfirmSlugChangeFormHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $confirmSlugChangeFormHelper;

    /** @var LocalizedSlugWithRedirectType */
    private $formType;

    protected function setUp(): void
    {
        $this->confirmSlugChangeFormHelper = $this->createMock(ConfirmSlugChangeFormHelper::class);
        $this->formType = new SlugWithRedirectType($this->confirmSlugChangeFormHelper);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $slugifyFormHelper = $this->createMock(SlugifyFormHelper::class);

        return [
            new PreloadedExtension(
                [
                    SlugWithRedirectType::class => $this->formType,
                    SlugType::class => new SlugType($slugifyFormHelper),
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(SlugWithRedirectType::NAME, $this->formType->getBlockPrefix());
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(
        TextSlugPrototypeWithRedirect $defaultData,
        array $submittedData,
        TextSlugPrototypeWithRedirect $expectedData,
        array $options = []
    ) {
        $form = $this->factory->create(
            SlugWithRedirectType::class,
            $defaultData,
            array_merge(['source_field' => 'some_field'], $options)
        );

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        /** @var TextSlugPrototypeWithRedirect $data */
        $data = $form->getData();

        $this->assertEquals($expectedData, $data);
    }

    public function submitProvider(): array
    {
        return [
            'text slug prototype with redirect' => [
                'defaultData' => new TextSlugPrototypeWithRedirect(),
                'submittedData' => [
                    'textSlugPrototype' => 'test-prototype',
                    'createRedirect' => true,
                ],
                'expectedData' => (new TextSlugPrototypeWithRedirect())
                    ->setTextSlugPrototype('test-prototype')->setCreateRedirect(true),
            ],
            'update text slug prototype without redirect' => [
                'defaultData' => (new TextSlugPrototypeWithRedirect())->setTextSlugPrototype('test-prototype'),
                'submittedData' => [
                    'textSlugPrototype' => 'test-prototype-new',
                    'createRedirect' => false,
                ],
                'expectedData' => (new TextSlugPrototypeWithRedirect())
                    ->setTextSlugPrototype('test-prototype-new')->setCreateRedirect(false),
            ],
            'text slug prototype with slashes and redirect' => [
                'defaultData' => new TextSlugPrototypeWithRedirect(),
                'submittedData' => [
                    'textSlugPrototype' => 'test-prefix/test-prototype',
                    'createRedirect' => true,
                ],
                'expectedData' => (new TextSlugPrototypeWithRedirect())
                    ->setTextSlugPrototype('test-prefix/test-prototype')->setCreateRedirect(true),
                'options' => ['allow_slashes' => true]
            ]
        ];
    }

    public function testSubmitError(): void
    {
        $defaultData = new TextSlugPrototypeWithRedirect();

        $form = $this->factory->create(
            SlugWithRedirectType::class,
            $defaultData,
            ['source_field' => 'some_field', 'allow_slashes' => false]
        );

        $form->submit(['textSlugPrototype' => 'test-prefix/test-prototype', 'createRedirect' => true]);
        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->getErrors(true));

        $error = $form->getErrors(true)->current();
        $this->assertEquals(
            'This value should contain only latin letters, numbers and symbols "-._~".',
            $error->getMessage()
        );
        $this->assertEquals(['{{ value }}' => '"test-prefix/test-prototype"'], $error->getMessageParameters());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                $this->callback(function (array $options) {
                    $this->assertEquals(TextSlugPrototypeWithRedirect::class, $options['data_class']);
                    $this->assertTrue($options['slug_suggestion_enabled']);
                    $this->assertTrue($options['create_redirect_enabled']);
                    $this->assertFalse($options['allow_slashes']);

                    return true;
                })
            );
        $resolver->expects($this->once())
            ->method('setRequired')
            ->with('source_field');

        $this->formType->configureOptions($resolver);
    }

    public function testBuildView()
    {
        $form = $this->createMock(FormInterface::class);
        $view = new FormView();
        $options = ['someOptionName' => 'someOptionValue'];

        $this->confirmSlugChangeFormHelper->expects($this->once())
            ->method('addConfirmSlugChangeOptions')
            ->with($view, $form, $options);

        $this->formType->buildView($view, $form, $options);
    }
}
