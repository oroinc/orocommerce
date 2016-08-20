<?php

namespace Oro\Bundle\CatalogBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Form\Type\ProductStepOneType;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryTreeType;
use Oro\Bundle\CatalogBundle\Provider\CategoryDefaultProductUnitProvider;

class ProductStepOneFormExtension extends AbstractTypeExtension
{
    /**
     * @var CategoryDefaultProductUnitProvider
     */
    protected $defaultProductUnitProvider;

    /**
     * @param CategoryDefaultProductUnitProvider $defaultProductUnitProvider
     */
    public function __construct(CategoryDefaultProductUnitProvider $defaultProductUnitProvider)
    {
        $this->defaultProductUnitProvider = $defaultProductUnitProvider;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductStepOneType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'category',
                CategoryTreeType::NAME,
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'oro.catalog.category.entity_label'
                ]
            )
        ;
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        if (!$form->isValid()) {
            return;
        }

        $category = $form->get('category')->getData();

        if ($category instanceof Category) {
            $this->defaultProductUnitProvider->setCategory($category);
        }
    }
}
