<?php

namespace OroB2B\Bundle\CatalogBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductStepOneType;
use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryTreeType;
use OroB2B\Bundle\CatalogBundle\Provider\CategoryDefaultProductUnitProvider;

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
                    'label' => 'orob2b.catalog.category.entity_label'
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
