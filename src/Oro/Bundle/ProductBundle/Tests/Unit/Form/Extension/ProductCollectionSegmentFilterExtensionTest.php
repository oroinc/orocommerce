<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ProductBundle\Service\ProductCollectionDefinitionConverter;
use Oro\Bundle\ProductBundle\Form\Extension\ProductCollectionSegmentFilterExtension;

use Oro\Bundle\SegmentBundle\Form\Type\SegmentFilterBuilderType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class ProductCollectionSegmentFilterExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductCollectionDefinitionConverter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $definitionConverter;

    /**
     * @var ProductCollectionSegmentFilterExtension
     */
    private $productCollectionSegmentFilterExtension;

    protected function setUp()
    {
        $this->definitionConverter = $this->createMock(ProductCollectionDefinitionConverter::class);
        $this->productCollectionSegmentFilterExtension =
            new ProductCollectionSegmentFilterExtension($this->definitionConverter);
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder **/
        $builder = $this->createMock(FormBuilderInterface::class);

        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $definitionBuilder **/
        $definitionBuilder = $this->createMock(FormBuilderInterface::class);
        $definitionBuilder
            ->expects($this->once())
            ->method('addEventListener')
            ->with(FormEvents::PRE_SET_DATA, [$this->productCollectionSegmentFilterExtension, 'onPreSetData']);

        $builder
            ->expects($this->once())
            ->method('get')
            ->with('definition')
            ->willReturn($definitionBuilder);

        $options = [];
        $this->productCollectionSegmentFilterExtension->buildForm($builder, $options);
    }

    public function testOnPreSetDataWithNullData()
    {
        /** @var FormInterface $form */
        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, null);

        $this->definitionConverter
            ->expects($this->never())
            ->method('getDefinitionParts');

        $this->productCollectionSegmentFilterExtension->onPreSetData($event);
    }

    public function testOnPreSetDataWithNotNullData()
    {
        /** @var FormInterface $form */
        $form = $this->createMock(FormInterface::class);
        $definition = '{filters:[{columnName: "id", criterion: {filter: "someFilter"}}]}';
        $newDefinition = '{filters:[{columnName: "id", criterion: {filter: "someNewFilter"}}]}';
        $event = new FormEvent($form, $definition);

        $this->definitionConverter
            ->expects($this->once())
            ->method('getDefinitionParts')
            ->with($definition)
            ->willReturn([
                ProductCollectionDefinitionConverter::DEFINITION_KEY => $newDefinition,
                ProductCollectionDefinitionConverter::EXCLUDED_FILTER_KEY => '',
                ProductCollectionDefinitionConverter::INCLUDED_FILTER_KEY => ''
            ]);

        $this->productCollectionSegmentFilterExtension->onPreSetData($event);

        $this->assertEquals($newDefinition, $event->getData());
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(
            SegmentFilterBuilderType::class,
            $this->productCollectionSegmentFilterExtension->getExtendedType()
        );
    }
}
