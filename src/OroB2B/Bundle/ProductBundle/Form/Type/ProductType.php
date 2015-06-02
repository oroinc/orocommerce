<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\PricingBundle\Form\Type\ProductPriceCollectionType;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingService;
use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryTreeType;

class ProductType extends AbstractType
{
    /**
     * @var RoundingService
     */
    protected $roundingService;

    /**
     * @param RoundingService $roundingService
     */
    public function __construct(RoundingService $roundingService)
    {
        $this->roundingService = $roundingService;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sku', 'text', ['required' => true, 'label' => 'orob2b.product.sku.label'])
            ->add('category', CategoryTreeType::NAME, ['required' => false, 'label' => 'orob2b.product.category.label'])
            ->add(
                'unitPrecisions',
                ProductUnitPrecisionCollectionType::NAME,
                [
                    'label' => 'orob2b.product.unit_precisions.label',
                    'tooltip' => 'orob2b.product.form.tooltip.unit_precision',
                    'required' => false
                ]
            )
            ->add(
                'prices',
                ProductPriceCollectionType::NAME,
                [
                    'label' => 'orob2b.product.prices.label',
                    'required' => false
                ]
            )
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmitData']);
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmitData(FormEvent $event)
    {
        $data = $event->getData();

        if (!isset($data['unitPrecisions']) || !isset($data['prices'])) {
            return;
        }

        $unitPrecisions = [];
        foreach ($data['unitPrecisions'] as $unitPrecision) {
            $unitPrecisions[$unitPrecision['unit']] = $unitPrecision['precision'];
        }

        foreach ($data['prices'] as &$price) {
            if (array_key_exists($price['unit'], $unitPrecisions)) {
                $price['quantity'] = $this->roundingService
                    ->round($price['quantity'], $unitPrecisions[$price['unit']]);
            }
        }

        $event->setData($data);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'OroB2B\Bundle\ProductBundle\Entity\Product',
            'intention' => 'product',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'orob2b_product';
    }
}
