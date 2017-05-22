<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Service\ProductCollectionDefinitionConverter;
use Oro\Bundle\QueryDesignerBundle\Validator\NotBlankFilters;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentFilterBuilderType;
use Oro\Component\WebCatalog\Form\PageVariantType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Valid;

class ProductCollectionVariantType extends AbstractType implements DataMapperInterface
{
    const NAME = 'oro_product_collection_variant';
    const PRODUCT_COLLECTION_SEGMENT = 'productCollectionSegment';
    const INCLUDED_PRODUCTS = 'includedProducts';
    const EXCLUDED_PRODUCTS = 'excludedProducts';

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
        $builder
            ->add(
                self::PRODUCT_COLLECTION_SEGMENT,
                SegmentFilterBuilderType::NAME,
                [
                    'label' => false,
                    'segment_entity' => Product::class,
                    'segment_columns' => ['id', 'sku'],
                    'segment_name_template' => 'Product Collection %s',
                    'add_name_field' => true,
                    'name_field_required' => false,
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
                        new Valid(),
                        new NotBlankFilters(['message' => 'oro.product.product_collection.blank_filters_or_included']),
                    ],
                    'error_bubbling' => false,
                    'field_event_listeners' => [
                        'definition' => [
                            FormEvents::PRE_SET_DATA => function (FormEvent $event) {
                                $definition = $event->getData();

                                if ($definition) {
                                    $definitionParts = $this->definitionConverter->getDefinitionParts($definition);

                                    $event->setData(
                                        $definitionParts[ProductCollectionDefinitionConverter::DEFINITION_KEY]
                                    );
                                }
                            }
                        ]
                    ]
                ]
            )
            ->add(self::INCLUDED_PRODUCTS, HiddenType::class, ['mapped' => false])
            ->add(self::EXCLUDED_PRODUCTS, HiddenType::class, ['mapped' => false])
            ->setDataMapper($this);

        // Make segment name required for existing segments
        $builder->get('productCollectionSegment')->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $segment = $event->getData();
                $eventListeners = $event->getForm()->getConfig()->getOption('field_event_listeners');
                if ($segment instanceof Segment && $segment->getId()) {
                    FormUtils::replaceField(
                        $event->getForm()->getParent(),
                        'productCollectionSegment',
                        [
                            'name_field_required' => true,
                            'field_event_listeners' => $eventListeners
                        ]
                    );
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return PageVariantType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'content_variant_type' => ProductCollectionContentVariantType::TYPE,
            'results_grid' => 'product-collection-grid',
            'included_products_grid' => 'product-collection-included-products-grid',
            'excluded_products_grid' => 'product-collection-excluded-products-grid'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['results_grid'] = $options['results_grid'];
        $view->vars['includedProductsGrid'] = $options['included_products_grid'];
        $view->vars['excludedProductsGrid'] = $options['excluded_products_grid'];

        $segmentDefinitionView = $view->children[self::PRODUCT_COLLECTION_SEGMENT]->children['definition'];
        $view->vars['segmentDefinitionFieldName'] = $segmentDefinitionView->vars['full_name'];
        $view->vars['segmentDefinition'] = $segmentDefinitionView->vars['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function mapDataToForms($data, $forms)
    {
        /** @var Form[]|\Traversable $forms */
        $forms = iterator_to_array($forms);

        if ($data) {
            /** @var Segment $segment */
            $segment = $data->getProductCollectionSegment();

            $definitionParts = $this->definitionConverter->getDefinitionParts($segment->getDefinition());

            $forms[self::PRODUCT_COLLECTION_SEGMENT]->setData($segment);

            if (isset($forms[self::INCLUDED_PRODUCTS])) {
                $forms[self::INCLUDED_PRODUCTS]
                    ->setData($definitionParts[ProductCollectionDefinitionConverter::INCLUDED_FILTER_KEY]);
            }

            if (isset($forms[self::EXCLUDED_PRODUCTS])) {
                $forms[self::EXCLUDED_PRODUCTS]
                    ->setData($definitionParts[ProductCollectionDefinitionConverter::EXCLUDED_FILTER_KEY]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mapFormsToData($forms, &$data)
    {
        /** @var Form[]|\Traversable $forms */
        $forms = iterator_to_array($forms);

        /** @var Segment $segment */
        $segment = $forms[self::PRODUCT_COLLECTION_SEGMENT]->getData();

        $segmentDefinition = $this->definitionConverter->putConditionsInDefinition(
            $segment->getDefinition(),
            $forms[self::EXCLUDED_PRODUCTS]->getData(),
            $forms[self::INCLUDED_PRODUCTS]->getData()
        );

        $segment->setDefinition($segmentDefinition);

        $data->setProductCollectionSegment($segment);
    }
}
