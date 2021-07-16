<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductAttributePriceCollectionType extends AbstractType
{
    const NAME = 'oro_pricing_product_attribute_price_collection';

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var PriceAttributeProductPrice $price */
        $price = current($form->getData());
        $priceAttribute = null;
        $currencies = [];
        $units = [];
        $unitsLabels = [];

        if ($price) {
            $priceAttribute = $price->getPriceList();
            $currencies = $priceAttribute->getCurrencies();
            $units = $price->getProduct()->getAvailableUnits();
            $unitsLabels = array_combine(array_keys($units), array_keys($units));
        }

        $unitsWithPriceAttributes = [];
        /** @var PriceAttributeProductPrice $v */
        foreach ($view->vars['value'] as $v) {
            $unitCode = $v->getUnit()->getCode();
            $unitsWithPriceAttributes[$unitCode] = true;
            if (!array_key_exists($unitCode, $units)) {
                if ($v->getPrice() && $v->getPrice()->getValue() !== null) {
                    $unitsLabels[$unitCode] = $this->translator
                        ->trans('oro.product.productunit.removed', ['{title}' => $unitCode]);
                }
            }
        }

        $view->vars['currencies'] = $currencies;
        $view->vars['units'] = array_intersect_key($unitsLabels, $unitsWithPriceAttributes);
        $view->vars['label'] = $priceAttribute->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'entry_type' => ProductAttributePriceType::class,
            ]
        );
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
