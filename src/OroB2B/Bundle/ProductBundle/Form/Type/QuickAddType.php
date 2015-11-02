<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use OroB2B\Bundle\ProductBundle\Form\DataTransformer\BulkProductsTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class QuickAddType extends AbstractType
{
    const NAME = 'orob2b_product_quick_add';

    const PRODUCTS_FIELD_NAME = 'products';
    const COMPONENT_FIELD_NAME = 'component';
    const ADDITIONAL_FIELD_NAME = 'additional';
    const BULK_PRODUCTS_FIELD_NAME = 'bulk_products';

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
                        'validation_required' => $options['validation_required']
                    ],
                    'error_bubbling' => true,
                    'constraints' => [new NotBlank(['message' => 'orob2b.product.at_least_one_item'])],
                    'add_label' => 'orob2b.product.form.add_row'
                ]
            )
//            ->add(
//                self::BULK_PRODUCTS_FIELD_NAME,
//                'textarea',
//                [
//                    'required' => false,
//                    'label' => 'Copy & Paste'
//                ]
//            )
            ->add(
                self::COMPONENT_FIELD_NAME,
                'hidden'
            )
            ->add(
                self::ADDITIONAL_FIELD_NAME,
                'hidden'
            );

            //$builder->addModelTransformer(new BulkProductsTransformer());
//            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
//
//                /** @var Request $request */
//                $request = $event->getData();
//
//                $transformer = new BulkProductsTransformer();
//                $newData = $transformer->reverseTransform($request->request->get(self::NAME));
//
//                $request->request->set(self::NAME, $newData);
//            }, 1000);

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_required' => false
            ]
        );
        $resolver->setAllowedTypes('validation_required', 'bool');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
