<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type;

use Oro\Bundle\RedirectBundle\Form\Type\SlugType;
use Oro\Bundle\RedirectBundle\Helper\SlugifyFormHelper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SlugTypeTest extends FormIntegrationTestCase
{
    /**
     * @var SlugifyFormHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $slugifyFormHelper;

    /**
     * @var SlugType
     */
    protected $formType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->slugifyFormHelper = $this->createMock(SlugifyFormHelper::class);
        $this->formType = new SlugType($this->slugifyFormHelper);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(SlugType::NAME, $this->formType->getBlockPrefix());
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())->method('setDefaults')->with(
            $this->callback(
                function (array $options) {
                    $this->assertTrue($options['slug_suggestion_enabled']);
                    $this->assertEquals($options['slugify_route'], 'oro_api_slugify_slug');

                    return true;
                }
            )
        );
        $resolver->expects($this->once())->method('setRequired')->with('source_field');
        $resolver->expects($this->once())->method('setDefined')->with('constraints');

        $this->formType->configureOptions($resolver);
    }

    public function testBuildView()
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $view = new FormView();
        $options = ['someOptionName' => 'someOptionValue'];

        $this->slugifyFormHelper->expects($this->once())
            ->method('addSlugifyOptions')
            ->with($view, $options);

        $this->formType->buildView($view, $form, $options);
    }
}
