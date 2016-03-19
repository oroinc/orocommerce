<?php

namespace OroB2B\Bundle\TaxBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use OroB2B\Bundle\TaxBundle\Provider\TaxSubtotalProvider;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderLineItemType;

class OrderLineItemTypeExtension extends AbstractTypeExtension
{
    /** @var TaxationSettingsProvider */
    protected $taxationSettingsProvider;

    /** @var TaxManager */
    protected $taxManager;

    /** @var TaxSubtotalProvider */
    protected $taxSubtotalProvider;

    /**
     * @param TaxationSettingsProvider $taxationSettingsProvider
     * @param TaxManager $taxManager
     * @param TaxSubtotalProvider $taxSubtotalProvider
     */
    public function __construct(
        TaxationSettingsProvider $taxationSettingsProvider,
        TaxManager $taxManager,
        TaxSubtotalProvider $taxSubtotalProvider
    ) {
        $this->taxationSettingsProvider = $taxationSettingsProvider;
        $this->taxManager = $taxManager;
        $this->taxSubtotalProvider = $taxSubtotalProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return OrderLineItemType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->taxationSettingsProvider->isEnabled()) {
            return;
        }

        $this->taxSubtotalProvider->setEditMode(true);

        $builder->add('taxes', 'hidden', ['required' => false, 'mapped' => false]);
    }

    /** {@inheritdoc} */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!$this->taxationSettingsProvider->isEnabled()) {
            return;
        }

        $entity = $form->getData();
        if (!$entity) {
            return;
        }

        $view->children['taxes']->vars['result'] = $this->taxManager->getTax($entity);
    }

    /** {@inheritdoc} */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setNormalizer(
            'sections',
            function (Options $options, array $sections) {
                $sections['taxes'] = ['data' => ['taxes' => []], 'order' => 50];

                return $sections;
            }
        );
    }
}
