<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\ProductBundle\Form\Type\QuantityType;

class QuantityParentTypeStub extends AbstractType
{
    const NAME = 'stub_quantity_parent_type';

    /** @var array */
    protected $quantityOptions = [];

    /** {@inheritdoc} */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('productField', 'entity', ['class' => 'Oro\Bundle\ProductBundle\Entity\Product'])
            ->add('productUnitField', 'entity', ['class' => 'Oro\Bundle\ProductBundle\Entity\ProductUnit'])
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
