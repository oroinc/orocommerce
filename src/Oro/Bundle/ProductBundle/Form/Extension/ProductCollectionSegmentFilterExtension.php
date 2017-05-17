<?php

namespace Oro\Bundle\ProductBundle\Form\Extension;

use Oro\Bundle\ProductBundle\Service\ProductCollectionDefinitionConverter;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentFilterBuilderType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * This extension is used to transform definition of Segment in SegmentFilterBuilderType.
 * It's done via extension because it's not possible to add event listener in ProductCollectionVariantType as
 * productCollectionSegment form can be recreated insided it and then listener wouldn't be invoked.
 */
class ProductCollectionSegmentFilterExtension extends AbstractTypeExtension
{
    /**
     * @var ProductCollectionDefinitionConverter
     */
    private $definitionConverter;

    /**
     * @param ProductCollectionDefinitionConverter $definitionConverter
     */
    public function __construct(ProductCollectionDefinitionConverter $definitionConverter)
    {
        $this->definitionConverter = $definitionConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->get('definition')->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        $definition = $event->getData();

        if ($definition) {
            $definitionParts = $this->definitionConverter->getDefinitionParts($definition);

            $event->setData($definitionParts[ProductCollectionDefinitionConverter::DEFINITION_KEY]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return SegmentFilterBuilderType::class;
    }
}
