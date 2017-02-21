<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type;

use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;
use Oro\Bundle\RedirectBundle\Form\Type\SlugType;
use Oro\Bundle\RedirectBundle\Form\Type\SlugWithRedirectType;
use Oro\Bundle\RedirectBundle\Helper\ConfirmSlugChangeFormHelper;
use Oro\Bundle\RedirectBundle\Helper\SlugifyFormHelper;
use Oro\Bundle\RedirectBundle\Model\TextSlugPrototypeWithRedirect;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SlugWithRedirectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ConfirmSlugChangeFormHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $confirmSlugChangeFormHelper;

    /**
     * @var LocalizedSlugWithRedirectType
     */
    protected $formType;

    protected function setUp()
    {
        $this->confirmSlugChangeFormHelper = $this->getMockBuilder(ConfirmSlugChangeFormHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->formType = new SlugWithRedirectType($this->confirmSlugChangeFormHelper);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions()
    {
        /** @var SlugifyFormHelper $slugifyFormHelper */
        $slugifyFormHelper = $this->createMock(SlugifyFormHelper::class);
        
        return [
            new PreloadedExtension(
                [
                    SlugType::NAME => new SlugType($slugifyFormHelper),
                ],
                []
            )
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(SlugWithRedirectType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(SlugWithRedirectType::NAME, $this->formType->getBlockPrefix());
    }

    /**
     * @dataProvider submitProvider
     *
     * @param TextSlugPrototypeWithRedirect $defaultData
     * @param array $submittedData
     * @param TextSlugPrototypeWithRedirect $expectedData
     */
    public function testSubmit(
        TextSlugPrototypeWithRedirect $defaultData,
        $submittedData,
        TextSlugPrototypeWithRedirect $expectedData
    ) {
        $form = $this->factory->create($this->formType, $defaultData, ['source_field' => 'some_field']);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        /** @var TextSlugPrototypeWithRedirect $data */
        $data = $form->getData();

        $this->assertEquals($expectedData, $data);
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'text slug prototype with redirect' => [
                'defaultData'   => new TextSlugPrototypeWithRedirect(),
                'submittedData' => [
                    'textSlugPrototype' => 'test-prototype',
                    'createRedirect' => true,
                ],
                'expectedData'  => (new TextSlugPrototypeWithRedirect())
                    ->setTextSlugPrototype('test-prototype')->setCreateRedirect(true),
            ],
            'update text slug prototype without redirect' => [
                'defaultData'   => (new TextSlugPrototypeWithRedirect())->setTextSlugPrototype('test-prototype'),
                'submittedData' => [
                    'textSlugPrototype' => 'test-prototype-new',
                    'createRedirect' => false,
                ],
                'expectedData'  => (new TextSlugPrototypeWithRedirect())
                    ->setTextSlugPrototype('test-prototype-new')->setCreateRedirect(false),
            ],
        ];
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())->method('setDefaults')->with(
            $this->callback(
                function (array $options) {
                    $this->assertEquals(TextSlugPrototypeWithRedirect::class, $options['data_class']);
                    $this->assertTrue($options['slug_suggestion_enabled']);
                    $this->assertTrue($options['create_redirect_enabled']);

                    return true;
                }
            )
        );
        $resolver->expects($this->once())->method('setRequired')->with('source_field');

        $this->formType->configureOptions($resolver);
    }
    
    public function testBuildView()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $view = new FormView();
        $options = ['someOptionName' => 'someOptionValue'];

        $this->confirmSlugChangeFormHelper->expects($this->once())
            ->method('addConfirmSlugChangeOptions')
            ->with($view, $form, $options);

        $this->formType->buildView($view, $form, $options);
    }
}
