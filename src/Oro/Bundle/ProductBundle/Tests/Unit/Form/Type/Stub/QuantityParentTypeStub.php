<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class QuantityParentTypeStub extends AbstractType
{
    const NAME = 'stub_quantity_parent_type';

    /** @var array */
    protected $quantityOptions = [];

    /** {@inheritdoc} */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('productField', EntityType::class, ['class' => 'Oro\Bundle\ProductBundle\Entity\Product'])
            ->add('productUnitField', EntityType::class, ['class' => 'Oro\Bundle\ProductBundle\Entity\ProductUnit'])
            ->add('quantityField', QuantityType::class, $this->quantityOptions);
    }

    /** {@inheritdoc} */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    public function setQuantityOptions(array $quantityOptions = [])
    {
        $this->quantityOptions = $quantityOptions;
    }
}
