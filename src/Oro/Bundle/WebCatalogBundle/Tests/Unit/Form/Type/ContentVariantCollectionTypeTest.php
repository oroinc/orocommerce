<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Form\EventListener\ContentVariantCollectionResizeSubscriber;
use Oro\Bundle\WebCatalogBundle\Form\Type\ContentVariantCollectionType;
use Oro\Bundle\WebCatalogBundle\Model\ContentVariantFormPrototype;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;
use Symfony\Bridge\Doctrine\Form\EventListener\MergeDoctrineCollectionListener;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContentVariantCollectionTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContentVariantTypeRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $variantTypeRegistry;

    /** @var ContentVariantCollectionType */
    private $type;

    protected function setUp(): void
    {
        $this->variantTypeRegistry = $this->createMock(ContentVariantTypeRegistry::class);

        $this->type = new ContentVariantCollectionType($this->variantTypeRegistry);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_web_catalog_content_variant_collection', $this->type->getBlockPrefix());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->any())
            ->method('setDefault')
            ->withConsecutive(
                ['prototype_name', '__variant_idx__'],
                ['entry_options', []]
            );
        $this->type->configureOptions($resolver);
    }

    public function testBuildView()
    {
        $form = $this->createMock(FormInterface::class);
        $view = $this->createMock(FormView::class);
        $subformView = $this->createMock(FormView::class);

        $subform = $this->createMock(FormInterface::class);
        $subform->expects($this->once())
            ->method('setParent')
            ->with($form)
            ->willReturnSelf();
        $subform->expects($this->once())
            ->method('createView')
            ->with($view)
            ->willReturn($subformView);
        $prototypes = [
            'name' => new ContentVariantFormPrototype($subform, 'title')
        ];

        $config = $this->createMock(FormConfigInterface::class);
        $config->expects($this->once())
            ->method('hasAttribute')
            ->with('prototypes')
            ->willReturn(true);
        $config->expects($this->once())
            ->method('getAttribute')
            ->with('prototypes')
            ->willReturn($prototypes);

        $form->expects($this->atLeastOnce())
            ->method('getConfig')
            ->willReturn($config);

        $this->type->buildView($view, $form, ['prototype_name' => 'test']);

        $this->assertArrayHasKey('prototype_name', $view->vars);
        $this->assertEquals('test', $view->vars['prototype_name']);

        $this->assertArrayHasKey('prototypes', $view->vars);
        $expectedPrototypes = [
            'name' => [
                'title' => 'title',
                'form' => $subformView
            ]
        ];
        $this->assertEquals($expectedPrototypes, $view->vars['prototypes']);
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->exactly(2))
            ->method('addEventSubscriber')
            ->withConsecutive(
                [$this->isInstanceOf(MergeDoctrineCollectionListener::class)],
                [$this->isInstanceOf(ContentVariantCollectionResizeSubscriber::class)]
            );

        $variantType = $this->createMock(ContentVariantTypeInterface::class);
        $variantType->expects($this->any())
            ->method('getFormType')
            ->willReturn('form.type');
        $variantType->expects($this->any())
            ->method('getTitle')
            ->willReturn('title');
        $variantType->expects($this->any())
            ->method('getName')
            ->willReturn('name');
        $this->variantTypeRegistry->expects($this->once())
            ->method('getAllowedContentVariantTypes')
            ->willReturn([$variantType]);

        $subform = $this->createMock(FormInterface::class);
        $subformBuilder = $this->createMock(FormBuilderInterface::class);
        $subformBuilder->expects($this->once())
            ->method('getForm')
            ->willReturn($subform);

        $builder->expects($this->once())
            ->method('create')
            ->with('_p_', 'form.type', ['required' => true])
            ->willReturn($subformBuilder);

        $builder->expects($this->once())
            ->method('setAttribute')
            ->with(
                'prototypes',
                [
                    'name' => new ContentVariantFormPrototype($subform, 'title')
                ]
            );

        $this->type->buildForm($builder, ['prototype_name' => '_p_', 'required' => true, 'entry_options' => []]);
    }
}
