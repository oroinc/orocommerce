<?php

namespace Oro\Bundle\VisibilityBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;
use Oro\Bundle\VisibilityBundle\Form\Type\EntityVisibilityType;

class CategoryFormExtension extends AbstractTypeExtension
{
    /** @var string */
    protected $visibilityToAllClass;

    /** @var string */
    protected $visibilityToAccountGroupClass;

    /** @var string */
    protected $visibilityToAccountClass;

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return CategoryType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                EntityVisibilityType::VISIBILITY,
                EntityVisibilityType::NAME,
                [
                    'data' => $options['data'],
                    'targetEntityField' => 'category',
                    'allClass' => $this->visibilityToAllClass,
                    'accountGroupClass' => $this->visibilityToAccountGroupClass,
                    'accountClass' => $this->visibilityToAccountClass,
                ]
            );
    }

    /**
     * @param string $visibilityToAllClass
     */
    public function setVisibilityToAllClass($visibilityToAllClass)
    {
        $this->visibilityToAllClass = $visibilityToAllClass;
    }

    /**
     * @param string $visibilityToAccountGroupClass
     */
    public function setVisibilityToAccountGroupClass($visibilityToAccountGroupClass)
    {
        $this->visibilityToAccountGroupClass = $visibilityToAccountGroupClass;
    }

    /**
     * @param string $visibilityToAccountClass
     */
    public function setVisibilityToAccountClass($visibilityToAccountClass)
    {
        $this->visibilityToAccountClass = $visibilityToAccountClass;
    }
}
