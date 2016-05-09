<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use OroB2B\Bundle\ProductBundle\Provider;

use OroB2B\Bundle\ProductBundle\Model\ProductHolderInterface;

class ProductUnitListType extends AbstractType
{
    const NAME = 'orob2b_product_unitlist';

    /**
     * @var array
     */
    private $productUnits;

    /**
     * @param ProductUnitProvider $productUnitProvider
     */
    public function __construct(ProductUnitProvider $productUnitProvider)
    {
        $this->productUnits = $productUnitProvider->getAvailableProductUnits();
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'choices' => $this->productUnits
        ));
    }
    /**
     * @inheritDoc
     */
    public function getName()
    {
        return self::NAME;
    }

    public function getParent()
    {
        return 'choice';
    }
}
