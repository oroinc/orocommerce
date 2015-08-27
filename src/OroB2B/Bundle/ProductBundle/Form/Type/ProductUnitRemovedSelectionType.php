<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface;

class ProductUnitRemovedSelectionType extends AbstractType
{
    const NAME = 'orob2b_product_unit_removed_selection';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @param string $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => $this->entityClass,
            'property' => 'code',
            'compact' => false,
            'required' => true,
            'empty_label' => 'orob2b.product.productunit.removed',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ProductUnitSelectionType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->options = $options;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm()->getParent();
        if (!$form) {
            return;
        }

        /* @var $productUnitHolder ProductUnitHolderInterface */
        $productUnitHolder = $form ? $form->getData() : null;

        $choices = [];

        $productUnitOptions = [
            'required' => $this->options['required'],
            'label' => $this->options['label'],
            'compact' => $this->options['compact'],
        ];

        if ($productUnitHolder && null !== $productUnitHolder->getEntityIdentifier()) {
            $product = $productUnitHolder->getProductHolder()->getProduct();
            if ($product) {
                foreach ($product->getUnitPrecisions() as $unitPrecision) {
                    $choices[] = $unitPrecision->getUnit();
                }
            }
            $productUnit = $productUnitHolder->getProductUnit();
            if (!$productUnit || ($product && !in_array($productUnit, $choices, true))) {
                $emptyValueTitle = $this->translator->trans($this->options['empty_label'], [
                    '{title}' => $productUnitHolder->getProductUnitCode(),
                ]);
                $productUnitOptions['empty_value'] =  $emptyValueTitle;
            }
            $productUnitOptions['choices'] = $choices;
        }

        $form->add(
            'productUnit',
            $this->getParent(),
            $productUnitOptions
        );
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $event->getForm()->getParent()->add(
            'productUnit',
            ProductUnitSelectionType::NAME,
            [
                'label' => $this->options['label'],
            ]
        );
    }
}
