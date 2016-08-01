<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;

class ProductSelectType extends AbstractType
{
    const NAME = 'orob2b_product_select';
    const DATA_PARAMETERS = 'data_parameters';

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
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                self::DATA_PARAMETERS => [],
                'autocomplete_alias' => 'orob2b_product_visibility_limited',
                'create_form_route' => 'orob2b_product_create',
                'empty_label' => 'orob2b.product.removed',
                'configs' => [
                    'placeholder' => 'orob2b.product.form.choose',
                    'result_template_twig' => 'OroB2BProductBundle:Product:Autocomplete/result.html.twig',
                    'selection_template_twig' => 'OroB2BProductBundle:Product:Autocomplete/selection.html.twig',
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getParent()
    {
        return OroEntitySelectOrCreateInlineType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->options = $options;
    }

    /**
     * {@inheritDoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!empty($options[self::DATA_PARAMETERS])) {
            $view->vars['attr']['data-select2_query_additional_params'] = json_encode(
                [self::DATA_PARAMETERS => $options[self::DATA_PARAMETERS]]
            );
        }

        $form = $form->getParent();

        /* @var $productHolder ProductHolderInterface */
        $productHolder = $form ? $form->getData() : null;

        if (!$productHolder instanceof ProductHolderInterface || !$productHolder->getEntityIdentifier()) {
            return;
        }

        if (!$productHolder->getProduct()) {
            $emptyValueTitle = $this->translator->trans(
                $options['empty_label'],
                ['{title}' => $productHolder->getProductSku()]
            );
            $view->vars['configs']['placeholder'] = $emptyValueTitle;
        }
    }
}
