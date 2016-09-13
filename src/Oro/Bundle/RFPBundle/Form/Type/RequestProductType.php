<?php

namespace Oro\Bundle\RFPBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;

class RequestProductType extends AbstractType
{
    const NAME = 'oro_rfp_request_product';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var ProductUnitLabelFormatter
     */
    protected $labelFormatter;

    /**
     * @param ProductUnitLabelFormatter $labelFormatter
     */
    public function __construct(ProductUnitLabelFormatter $labelFormatter)
    {
        $this->labelFormatter = $labelFormatter;
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('product', ProductSelectType::NAME, [
                'required'  => true,
                'label'     => 'oro.product.entity_label',
                'create_enabled' => false,
                'data_parameters' => [
                    'scope' => 'rfp'
                ]
            ])
            ->add('requestProductItems', RequestProductItemCollectionType::NAME, [
                'label'     => 'oro.rfp.requestproductitem.entity_plural_label',
                'add_label' => 'oro.rfp.requestproductitem.add_label',
                'options' => [
                    'compact_units' => $options['compact_units'],
                ],
            ])
            ->add('comment', 'textarea', [
                'required'  => false,
                'label'     => 'oro.rfp.requestproduct.comment.label',
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'compact_units' => false,
            'intention'  => 'rfp_request_product',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
            'page_component' => 'oroui/js/app/components/view-component',
            'page_component_options' => ['view' => 'ororfp/js/app/views/line-item-view'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $units = [];

        /* @var $products Product[] */
        $products = [];

        if ($view->vars['value']) {
            /* @var $requestProduct RequestProduct */
            $requestProduct = $view->vars['value'];

            if ($requestProduct->getProduct()) {
                $product = $requestProduct->getProduct();
                $products[$product->getId()] = $product;
            }
        }

        foreach ($products as $product) {
            $units[$product->getId()] = [];

            foreach ($product->getAvailableUnitCodes() as $unitCode) {
                $units[$product->getId()][$unitCode] = $this->labelFormatter->format(
                    $unitCode,
                    $options['compact_units']
                );
            }
        }

        $componentOptions = [
            'units' => $units,
            'compactUnits' => $options['compact_units'],
        ];

        $view->vars['componentOptions'] = $componentOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['page_component'] = $options['page_component'];
        $view->vars['page_component_options'] = $options['page_component_options'];
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
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
