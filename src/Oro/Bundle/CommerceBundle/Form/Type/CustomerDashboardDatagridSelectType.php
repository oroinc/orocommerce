<?php

namespace Oro\Bundle\CommerceBundle\Form\Type;

use Oro\Bundle\CommerceBundle\ContentWidget\Provider\CustomerDashboardDatagridsProviderInterface;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Covers logic of selecting customer dashboard datagrid for content widget.
 */
class CustomerDashboardDatagridSelectType extends AbstractType
{
    public function __construct(
        private CustomerDashboardDatagridsProviderInterface $datagridContentWidgetTypeProvider
    ) {
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (empty($options['choices'])) {
            $options['configs']['placeholder'] =
                'oro.commerce.content_widget_type.customer_dashboard_datagrid.form.no_available_datagrid';
        }

        $view->vars = \array_replace_recursive($view->vars, ['configs' => $options['configs']]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'choices' => $this->datagridContentWidgetTypeProvider->getDatagrids(),
                'placeholder' => 'oro.commerce.content_widget_type.customer_dashboard_datagrid.form.choose_datagrid',
            ]
        );
    }

    #[\Override]
    public function getParent(): string
    {
        return Select2ChoiceType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_commerce_datagrid_content_widget_type_select';
    }
}
