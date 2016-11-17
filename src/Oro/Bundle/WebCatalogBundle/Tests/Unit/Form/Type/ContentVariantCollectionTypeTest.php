<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\WebCatalogBundle\Form\Type\ContentVariantCollectionType;
use Symfony\Bridge\Doctrine\Form\EventListener\MergeDoctrineCollectionListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContentVariantCollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentVariantCollectionType
     */
    protected $type;

    /**
     * @var EventSubscriberInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resizeSubscriber;

    protected function setUp()
    {
        $this->resizeSubscriber = $this->getMock(EventSubscriberInterface::class);

        $this->type = new ContentVariantCollectionType($this->resizeSubscriber);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_web_catalog_content_variant_collection', $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_web_catalog_content_variant_collection', $this->type->getBlockPrefix());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->getMockBuilder(OptionsResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resolver->expects($this->once())
            ->method('setDefault')
            ->with('prototype_name', '__name__');
        $this->type->configureOptions($resolver);
    }

    public function testBuildView()
    {
        $view = $this->getMockBuilder(FormView::class)
            ->disableOriginalConstructor()
            ->getMock();
        $form = $this->getMock(FormInterface::class);

        $this->type->buildView($view, $form, ['prototype_name' => 'test']);

        $this->assertArrayHasKey('prototype_name', $view->vars);
        $this->assertEquals('test', $view->vars['prototype_name']);
    }

    public function testBuildForm()
    {
        $builder = $this->getMock(FormBuilderInterface::class);
        $builder->expects($this->exactly(2))
            ->method('addEventSubscriber')
            ->withConsecutive(
                [$this->isInstanceOf(MergeDoctrineCollectionListener::class)],
                [$this->resizeSubscriber]
            );

        $this->type->buildForm($builder, []);
    }
}
