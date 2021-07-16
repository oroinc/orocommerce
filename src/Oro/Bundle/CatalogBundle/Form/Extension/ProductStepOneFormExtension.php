<?php

namespace Oro\Bundle\CatalogBundle\Form\Extension;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryTreeType;
use Oro\Bundle\CatalogBundle\Provider\CategoryDefaultProductUnitProvider;
use Oro\Bundle\ProductBundle\Form\Type\ProductStepOneType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ProductStepOneFormExtension extends AbstractTypeExtension
{
    /**
     * @var CategoryDefaultProductUnitProvider
     */
    protected $defaultProductUnitProvider;

    public function __construct(CategoryDefaultProductUnitProvider $defaultProductUnitProvider)
    {
        $this->defaultProductUnitProvider = $defaultProductUnitProvider;
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [ProductStepOneType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'category',
                CategoryTreeType::class,
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'oro.catalog.category.entity_label'
                ]
            )
        ;
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }

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
