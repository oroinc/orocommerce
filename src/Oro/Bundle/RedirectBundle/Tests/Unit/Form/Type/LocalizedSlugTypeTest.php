<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type;

use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\RedirectBundle\Helper\SlugifyFormHelper;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizedSlugTypeTest extends FormIntegrationTestCase
{
    /**
     * @var SlugifyFormHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $slugifyFormHelper;

    /**
     * @var LocalizedSlugType
     */
    private $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->slugifyFormHelper = $this->createMock(SlugifyFormHelper::class);
        $this->formType = new LocalizedSlugType($this->slugifyFormHelper);
    }

    public function testGetName()
    {
        $this->assertEquals(LocalizedSlugType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(LocalizedSlugType::NAME, $this->formType->getBlockPrefix());
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addEventListener')
            ->with(
                FormEvents::POST_SUBMIT,
                function () {
                }
            );

        $this->formType->buildForm($builder, []);
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())->method('setDefaults')->with(
            $this->callback(
                function (array $options) {
                    $this->assertEquals('oro_api_slugify_slug', $options['slugify_route']);
                    $this->assertTrue($options['slug_suggestion_enabled']);

                    return true;
                }
            )
        );
        $resolver->expects($this->once())->method('setDefined')->with('source_field');

        $this->formType->configureOptions($resolver);
    }

    public function testBuildView()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $view = new FormView();
        $options = ['someOptionName' => 'someOptionValue'];
        
        $this->slugifyFormHelper->expects($this->once())
            ->method('addSlugifyOptionsLocalized')
            ->with($view, $options);

        $this->formType->buildView($view, $form, $options);
    }
}
