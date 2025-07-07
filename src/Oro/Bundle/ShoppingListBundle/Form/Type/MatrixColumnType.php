<?php

namespace Oro\Bundle\ShoppingListBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RFPBundle\Provider\ProductRFPAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionColumn;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Allows to edit quantity of product variant from matrix form.
 */
class MatrixColumnType extends AbstractType
{
    private ?array $supportedForOrderInventoryStatuses = null;

    public function __construct(
        private readonly ProductRFPAvailabilityProvider $productRFPAvailabilityProvider,
        private readonly ConfigManager $configManager
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var MatrixCollectionColumn $column */
            $column = $event->getData();

            $quantityConfig = [
                'label' => false,
                'attr' => [
                    'placeholder' => 'oro.frontend.shoppinglist.view.qty.label',
                    'data-floating-error' => ''
                ],
                'precision' => 0,
            ];
            if ($column->product === null) {
                $quantityConfig['disabled'] = true;
            } else {
                $productUnit = $event->getForm()->getRoot()->getData()->unit;
                $scale = $column->product->getUnitPrecision($productUnit->getCode());
                $precision = $scale ? $scale->getPrecision() : 0;

                $quantityConfig['precision'] = $precision;
                $quantityConfig['attr']['data-validation'] = [
                    'decimal-precision' => [
                        'message' => 'oro.non_valid_precision',
                        'precision' => $precision,
                    ]
                ];
                $quantityConfig['attr']['data-precision'] = $precision;
                $quantityConfig['attr']['data-input-widget'] = 'number';
            }
            $event->getForm()->add('quantity', MatrixColumnQuantityType::class, $quantityConfig);
        });
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var MatrixCollectionColumn $column */
        $column = $form->getData();
        if ($column instanceof MatrixCollectionColumn) {
            $view->vars['label'] = $column->label;
            $view->vars['productId'] = $column->product?->getId();
            $view->vars['isEditable'] = $this->isFieldEditable($column);
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MatrixCollectionColumn::class,
        ]);
    }

    private function isFieldEditable(MatrixCollectionColumn $column): bool
    {
        if (!$column->product || !$column->product->isEnabled()) {
            return false;
        }

        $canBeAddedToRfqs = $this->productRFPAvailabilityProvider->isProductAllowedForRFP($column->product);

        if ($canBeAddedToRfqs) {
            return true;
        }

        if (is_null($this->supportedForOrderInventoryStatuses)) {
            $this->supportedForOrderInventoryStatuses = $this->configManager->get(
                'oro_order.frontend_product_visibility'
            ) ?? [];
        }

        return in_array(
            $column->product->getInventoryStatus()->getId(),
            $this->supportedForOrderInventoryStatuses,
            true
        );
    }
}
