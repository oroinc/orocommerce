<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use OroB2B\Bundle\ProductBundle\Form\Type\QuantityType;

class StubQuantityParentType extends AbstractType
{
    const NAME = 'stub_quantity_parent_type';

    /** @var array */
    protected $quantityOptions = [];

    /** {@inheritdoc} */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('productField', 'entity', ['class' => 'OroB2B\Bundle\ProductBundle\Entity\Product'])
            ->add('productUnitField', 'entity', ['class' => 'OroB2B\Bundle\ProductBundle\Entity\ProductUnit'])
            ->add('quantityField', QuantityType::NAME, $this->quantityOptions);
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param array $quantityOptions
     */
    public function setQuantityOptions(array $quantityOptions = [])
    {
        $this->quantityOptions = $quantityOptions;
    }
}
