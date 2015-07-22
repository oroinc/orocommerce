<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class ProductPriceCollectionType extends AbstractType
{
    const NAME = 'orob2b_pricing_product_price_collection';

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

    /**
     * @param ManagerRegistry $registry
     */
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'type' => ProductPriceType::NAME,
                'show_form_when_empty' => false,
                'options' => ['data_class' => $this->dataClass]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var PriceList[] $priceLists */
        $priceLists = $this->registry->getRepository($this->priceListClass)->findAll();

        $currencies = [];
        foreach ($priceLists as $priceList) {
            $currencies[$priceList->getId()] = $priceList->getCurrencies();
        }

        $view->vars['attr']['data-currencies'] = json_encode($currencies);
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
        return CollectionType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
