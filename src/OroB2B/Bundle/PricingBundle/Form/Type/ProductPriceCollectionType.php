<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

class ProductPriceCollectionType extends AbstractType
{
    const NAME = 'orob2b_pricing_product_price_collection';

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param ObjectManager $objectManager
     */
    public function __construct($objectManager)
    {
        $this->em = $objectManager;
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
     * @inheritdoc
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $priceLists = $this->em->getRepository('OroB2BPricingBundle:PriceList')->findAll();

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
