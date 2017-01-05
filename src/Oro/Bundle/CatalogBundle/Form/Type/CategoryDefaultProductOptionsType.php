<?php

namespace Oro\Bundle\CatalogBundle\Form\Type;

use Oro\Bundle\CatalogBundle\Visibility\CategoryDefaultProductUnitOptionsVisibilityInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryDefaultProductOptionsType extends AbstractType
{
    const NAME = 'oro_catalog_category_default_product_options';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var CategoryDefaultProductUnitOptionsVisibilityInterface
     */
    protected $defaultProductOptionsVisibility;

    /**
     * @param CategoryDefaultProductUnitOptionsVisibilityInterface $defaultProductOptionsVisibility
     */
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

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addUnitPrecisionField($builder);
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

    /**
     * @param FormBuilderInterface $builder
     */
    private function addUnitPrecisionField(FormBuilderInterface $builder)
    {
        $type = HiddenType::class;

        if ($this->defaultProductOptionsVisibility->isDefaultUnitPrecisionSelectionAvailable()) {
            $type = CategoryUnitPrecisionType::class;
        }

        $builder->add('unitPrecision', $type, [
            'label' => 'oro.catalog.category.unit.label',
            'required' => false,
        ]);
    }
}
