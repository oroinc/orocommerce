<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductAttributePriceCollectionType extends FormType
{
    const NAME = 'oro_pricing_product_attribute_price_collection';

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
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

        if ($price) {
            $priceAttribute = $price->getPriceList();
            $currencies = $priceAttribute->getCurrencies();
            $units = $price->getProduct()->getAvailableUnitCodes();
        }

        $priceAttributesUnits = [];
        /** @var PriceAttributeProductPrice $value */
        foreach ($view->vars['value'] as $v) {
            $unitCode = $v->getUnit()->getCode();
            $priceAttributesUnits[$unitCode] = $unitCode;
        }
        $addedUnit = array_diff($units, $priceAttributesUnits);
        $removedUnit = array_diff($priceAttributesUnits, $units);
        if (0 < count($removedUnit) && 0 < count($addedUnit)) {
            foreach ($view->vars['value'] as &$value) {
                if ($value->getUnit()->getCode() === reset($removedUnit)) {
                    $newUnit = $this->getManager()
                        ->getRepository(ProductUnit::class)
                        ->findOneBy(['code' => reset($addedUnit)]);
                    if (null === $value->getPrice()->getValue()) {
                        $value->setUnit($newUnit);
                    } else {
                        unset($units[reset($addedUnit)]);
                        $units[reset($removedUnit)] = reset($removedUnit) . ' - removed';
                    }
                }
            }
            unset($value);
        }

        $view->vars['currencies'] = $currencies;
        $view->vars['units'] = $units;
        $view->vars['label'] = $priceAttribute->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'type' => ProductAttributePriceType::NAME,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'collection';
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
     * @return ObjectManager|null
     */
    protected function getManager()
    {
        if (!$this->objectManager) {
            $this->objectManager = $this->registry->getManagerForClass(Product::class);
        }
        return $this->objectManager;
    }
}
