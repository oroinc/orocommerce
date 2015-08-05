<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Model\ProductHolderInterface;

class ProductRemovedSelectType extends AbstractType
{
    const NAME = 'orob2b_product_removed_select';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator) {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'autocomplete_alias' => 'orob2b_product',
            'create_form_route' => 'orob2b_product_create',
            'empty_label' => 'orob2b.product.removed',
            'required' => true,
            'configs' => [
                'placeholder' => 'orob2b.product.form.choose',
            ],
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
        return ProductSelectType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->options = $options;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm()->getParent();

        /* @var $productHolder ProductHolderInterface */
        $productHolder = $form ? $form->getData() : null;

        if (!$productHolder || !$productHolder instanceof ProductHolderInterface || null === $productHolder->getId()) {
            return;
        }

        $options = [
            'create_enabled' => $this->options['create_enabled'],
            'required' => $this->options['required'],
            'label' => $this->options['label'],
        ];

        if (!$productHolder->getProduct()) {
            $emptyValueTitle = $this->translator->trans($this->options['empty_label'], [
                '{title}' => $productHolder->getProductSku(),
            ]);
            $options['configs'] = [
                'placeholder' => $emptyValueTitle,
            ];

            $form->add('product', $this->getParent(), $options);
        }
    }
}
