<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ProductBundle\Form\DataTransformer\ProductCollectionTransformer;

class ProductRowCollectionType extends AbstractType
{
    const NAME = 'orob2b_product_row_collection';

    const ROW_COUNT = 5;

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $products = $event->getData();
            if (!$products) {
                return;
            }

            $form = $event->getForm();
            foreach ($products as $key => $product) {
                if ($product[ProductDataStorage::PRODUCT_SKU_KEY] === '' &&
                    $product[ProductDataStorage::PRODUCT_QUANTITY_KEY] === ''
                ) {
                    // disable row validation
                    $rowFormConfig = $form->get($key)->getConfig();
                    $formType = $rowFormConfig->getType()->getName();
                    $formOptions = $rowFormConfig->getOptions();
                    $formOptions['validation_groups'] = false;
                    $form->remove($key);
                    $form->add($key, $formType, $formOptions);
                } elseif ($product[ProductDataStorage::PRODUCT_QUANTITY_KEY] === '') {
                    // default quantity
                    $products[$key][ProductDataStorage::PRODUCT_QUANTITY_KEY] = '1';
                }
            }
            $event->setData($products);
        });

        // remove empty rows from data
        $builder->addModelTransformer(new ProductCollectionTransformer());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'type' => ProductRowType::NAME,
                'required' => false,
                'handle_primary' => false,
                'row_count' => self::ROW_COUNT
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['row_count'] = $options['row_count'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
