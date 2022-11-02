<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 *  Product price collection form type
 *  Used to handle collection of underlying  Product prices for types
 */
class ProductPriceCollectionType extends AbstractType
{
    const NAME = 'oro_pricing_product_price_collection';
    const VALIDATION_GROUP = 'ProductPriceCollection';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var string
     */
    protected $priceListClass;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $priceListClass
     */
    public function setPriceListClass($priceListClass)
    {
        $this->priceListClass = $priceListClass;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'entry_type' => ProductPriceType::class,
                'show_form_when_empty' => false,
                'entry_options' => ['data_class' => $this->dataClass],
                'validation_groups' => [self::VALIDATION_GROUP]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr']['data-currencies'] = json_encode(
            $this->registry->getRepository($this->priceListClass)->getCurrenciesIndexedByPricelistIds()
        );

        $view->vars['skip_optional_validation_group'] = true;

        unset($view->vars['attr']['data-validation-optional-group']);
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
    public function getParent()
    {
        return CollectionType::class;
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
