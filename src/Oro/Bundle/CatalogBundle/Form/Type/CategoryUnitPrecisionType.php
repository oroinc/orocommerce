<?php

namespace Oro\Bundle\CatalogBundle\Form\Type;

use Oro\Bundle\CatalogBundle\Visibility\CategoryDefaultProductUnitOptionsVisibilityInterface;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryUnitPrecisionType extends AbstractType
{
    const NAME = 'oro_catalog_category_unit_precision';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var CategoryDefaultProductUnitOptionsVisibilityInterface
     */
    protected $defaultProductOptionsVisibility;

    public function __construct(CategoryDefaultProductUnitOptionsVisibilityInterface $defaultProductOptionsVisibility)
    {
        $this->defaultProductOptionsVisibility = $defaultProductOptionsVisibility;
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addUnitField($builder);
        $this->addPrecisionField($builder);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
        ]);
    }

    /**
     * @return string
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

    private function addUnitField(FormBuilderInterface $builder)
    {
        $type = EntityIdentifierType::class;
        $options = [
            'class' => ProductUnit::class,
            'multiple' => false,
        ];
        if ($this->defaultProductOptionsVisibility->isDefaultUnitPrecisionSelectionAvailable()) {
            $type = ProductUnitSelectionType::class;
            $options = [
                'placeholder' => 'oro.catalog.category.unit.empty.value',
            ];
        }
        $builder->add('unit', $type, $options);
    }

    private function addPrecisionField(FormBuilderInterface $builder)
    {
        $type = HiddenType::class;
        $options = [
            'required' => false,
        ];
        if ($this->defaultProductOptionsVisibility->isDefaultUnitPrecisionSelectionAvailable()) {
            $type = IntegerType::class;
            $options['type'] = 'text';
        }

        $builder->add('precision', $type, $options);
    }
}
