<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\ProductBundle\Model\ProductRow;

class QuickAddType extends AbstractType
{
    const NAME = 'oro_product_quick_add';

    const PRODUCTS_FIELD_NAME = 'products';
    const COMPONENT_FIELD_NAME = 'component';
    const ADDITIONAL_FIELD_NAME = 'additional';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                self::PRODUCTS_FIELD_NAME,
                ProductRowCollectionType::NAME,
                [
                    'required' => false,
                    'options' => [
                        'validation_required' => $options['validation_required'],
                    ],
                    'error_bubbling' => true,
                    'constraints' => [new NotBlank(['message' => 'oro.product.at_least_one_item'])],
                    'add_label' => 'oro.product.form.add_row',
                    'products' => $options['products'],
                ]
            )
            ->add(
                self::COMPONENT_FIELD_NAME,
                'hidden'
            )
            ->add(
                self::ADDITIONAL_FIELD_NAME,
                'hidden'
            );

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_required' => false,
                'products' => null,
            ]
        );
        $resolver->setAllowedTypes('validation_required', 'bool');
        $resolver->setAllowedTypes('products', ['array', 'null']);
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

    /**
     * Remove duplicated products and combine their quantities
     *
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        if (!array_key_exists('products', $data)) {
            return;
        }
        $productBySkus = [];
        /** @var ProductRow $productRow */
        foreach ($data['products'] as $productRow) {
            if (empty($productRow['productSku']) || !isset($productRow['productQuantity'])) {
                // keep empty row so same amount of rows are rendered as default setup
                $productBySkus[] = $productRow;
                continue;
            }

            if (!isset($productBySkus[$productRow['productSku']])) {
                $productBySkus[$productRow['productSku']] = $productRow;
                continue;
            }

            $productBySkus[$productRow['productSku']]['productQuantity'] += $productRow['productQuantity'];
            // add an empty row instead of removed duplicate product
            $productBySkus[] = ['productSku' => '', 'productQuantity' => ''];
        }
        $data['products'] = array_values($productBySkus);
        $event->setData($data);
    }
}
