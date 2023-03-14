<?php

namespace Oro\Bundle\PaymentTermBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmQueryConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsProviderInterface;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;

/**
 * Modifies grid configuration to display Payment Term association properly.
 */
class CustomerDatagridListener
{
    private PaymentTermAssociationProvider $paymentTermAssociationProvider;
    private SelectedFieldsProviderInterface $selectedFieldsProvider;

    public function __construct(
        PaymentTermAssociationProvider $paymentTermAssociationProvider,
        SelectedFieldsProviderInterface $selectedFieldsProvider
    ) {
        $this->paymentTermAssociationProvider = $paymentTermAssociationProvider;
        $this->selectedFieldsProvider = $selectedFieldsProvider;
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        $associationNames = $this->getSelectedCustomerAssociations($event);
        if (!$associationNames) {
            return;
        }

        $customerGroupAssociationNames = $this->paymentTermAssociationProvider
            ->getAssociationNames(CustomerGroup::class);

        if (!$customerGroupAssociationNames) {
            return;
        }

        $config = $event->getConfig();
        $query = $config->getOrmQuery();
        $rootAlias = $query->getRootAlias();

        $aliases = [];
        foreach ($customerGroupAssociationNames as $customerGroupAssociationName) {
            $alias = 'agpt_' . $customerGroupAssociationName;
            $aliases[] = $alias;
            $query->addLeftJoin('customer_group.' . $customerGroupAssociationName, $alias);
        }

        $query->addSelect($this->getSelectPart($aliases, 'customer_group_payment_term', 'label'));

        foreach ($associationNames as $associationName) {
            $query->addSelect(
                $this->getSelectPart(
                    $aliases,
                    $associationName . '_resolved_id',
                    'id',
                    [sprintf('IDENTITY(%s.%s)', $rootAlias, $associationName)]
                )
            );

            $targetField = $this->paymentTermAssociationProvider->getTargetField(Customer::class, $associationName);
            $joinAlias = $query->getJoinAlias($rootAlias . '.' . $associationName);
            $prepend = $this->joinExists($query, $joinAlias) ? [$joinAlias . '.' . $targetField] : [];
            $query->addSelect(
                $this->getSelectPart(
                    $aliases,
                    $associationName . '_resolved_value',
                    $targetField,
                    $prepend
                )
            );
            $config->offsetSetByPath(
                sprintf('[filters][columns][%s]', $associationName),
                [
                    'type' => 'entity',
                    'data_name' => $associationName . '_resolved_id',
                    'options' => [
                        'field_options' => [
                            'multiple' => true,
                            'class' => PaymentTerm::class,
                            'choice_label' => 'label'
                        ]
                    ]
                ]
            );
            $config->offsetSetByPath(
                sprintf('[sorters][columns][%s][data_name]', $associationName),
                $associationName . '_resolved_value'
            );
            $config->offsetSetByPath(
                sprintf('[columns][%s][label]', $associationName),
                'oro.customer.payment_term_7c4f1e8e.label'
            );
            $config->offsetSetByPath(sprintf('[columns][%s][type]', $associationName), 'twig');
            $config->offsetSetByPath(sprintf('[columns][%s][frontend_type]', $associationName), 'html');
            $config->offsetSetByPath(
                sprintf('[columns][%s][template]', $associationName),
                '@OroPaymentTerm/PaymentTerm/column.html.twig'
            );
        }
    }

    private function getSelectPart(array $aliases, string $alias, string $fieldName, array $prepend = []): string
    {
        return sprintf(
            'COALESCE(%s) as %s',
            implode(
                ',',
                array_merge(
                    $prepend,
                    array_map(
                        function ($alias) use ($fieldName) {
                            return $alias . '.' . $fieldName;
                        },
                        $aliases
                    )
                )
            ),
            $alias
        );
    }

    private function getSelectedCustomerAssociations(BuildBefore $event): array
    {
        $config = $event->getConfig();
        $className = $config->getExtendedEntityClassName();
        if (!is_a(Customer::class, $className, true)) {
            return [];
        }

        $selectedFields =$this->selectedFieldsProvider
            ->getSelectedFields($event->getConfig(), $event->getDatagrid()->getParameters());

        $associationNames = $this->paymentTermAssociationProvider->getAssociationNames($className);

        return array_intersect($associationNames, $selectedFields);
    }

    private function joinExists(OrmQueryConfiguration $query, string $alias): bool
    {
        $joins = array_merge($query->getLeftJoins(), $query->getInnerJoins());
        foreach ($joins as $join) {
            if ($alias === $join['alias']) {
                return true;
            }
        }

        return false;
    }
}
