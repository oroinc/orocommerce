<?php

namespace OroB2B\Bundle\AccountBundle\Form\Extension;

use OroB2B\Bundle\AccountBundle\Form\Type\EntityVisibilityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;

use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryType;

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
                'visibility',
                EntityVisibilityType::NAME,
                [
                    'data' => $options['data'],
                    'visibilityToAllClass' => $this->visibilityToAllClass,
                    'visibilityToAccountGroupClass' => $this->visibilityToAccountGroupClass,
                    'visibilityToAccountClass' => $this->visibilityToAccountClass,
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
