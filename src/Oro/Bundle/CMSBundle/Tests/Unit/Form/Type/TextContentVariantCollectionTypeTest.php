<?php

namespace Oro\Bundle\CmsBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CMSBundle\Form\Type\TextContentVariantCollectionType;
use Oro\Bundle\CMSBundle\Form\Type\TextContentVariantType;

use Oro\Component\WebCatalog\Model\ContentVariantFormPrototype;

class TextContentVariantCollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var TextContentVariantCollectionType */
    protected $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->type = new TextContentVariantCollectionType();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_cms_text_content_variant_collection', $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_cms_text_content_variant_collection', $this->type->getBlockPrefix());
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->getMockBuilder(OptionsResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resolver->expects($this->any())
            ->method('setDefault')
            ->withConsecutive(
                ['prototype_name', '__variant_idx__']
            );
        $this->type->configureOptions($resolver);
    }

    public function testBuildView()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);

        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMockBuilder(FormView::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subform = $this->createMock(FormInterface::class);

        /** @var FormConfigInterface|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->createMock(FormConfigInterface::class);
        $config->expects($this->once())
            ->method('getAttribute')
            ->with('formPrototype')
            ->willReturn(new ContentVariantFormPrototype($subform));

        $form->expects($this->atLeastOnce())
            ->method('getConfig')
            ->willReturn($config);

        $this->type->buildView($view, $form, ['prototype_name' => 'test']);

        $this->assertArrayHasKey('prototype_name', $view->vars);
        $this->assertEquals('test', $view->vars['prototype_name']);

        $this->assertArrayHasKey('formPrototype', $view->vars);
        $this->assertEquals(new ContentVariantFormPrototype($subform), $view->vars['formPrototype']);
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $subform */
        $subform = $this->createMock(FormInterface::class);
        $subformBuilder = $this->createMock(FormBuilderInterface::class);
        $subformBuilder->expects($this->once())
            ->method('getForm')
            ->willReturn($subform);

        $builder->expects($this->once())
            ->method('create')
            ->with('_p_', TextContentVariantType::class, ['required' => true])
            ->willReturn($subformBuilder);

        $builder->expects($this->once())
            ->method('setAttribute')
            ->with('formPrototype', new ContentVariantFormPrototype($subform));

        $this->type->buildForm($builder, ['prototype_name' => '_p_', 'required' => true, 'entry_options' => []]);
    }
}
