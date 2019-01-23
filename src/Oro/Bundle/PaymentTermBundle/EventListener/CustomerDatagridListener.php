<?php

namespace Oro\Bundle\PaymentTermBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsProviderInterface;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;

/**
 * Modifies grid configuration to display Payment Term association properly.
 */
class CustomerDatagridListener
{
    /** @var PaymentTermAssociationProvider */
    private $paymentTermAssociationProvider;

    /**
     * @var SelectedFieldsProviderInterface
     */
    private $selectedFieldsProvider;

    /**
     * @param PaymentTermAssociationProvider $paymentTermAssociationProvider
     * @param SelectedFieldsProviderInterface $selectedFieldsProvider
     */
    public function __construct(
        PaymentTermAssociationProvider $paymentTermAssociationProvider,
        SelectedFieldsProviderInterface $selectedFieldsProvider
    ) {
        $this->paymentTermAssociationProvider = $paymentTermAssociationProvider;
        $this->selectedFieldsProvider = $selectedFieldsProvider;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
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
            $query->addSelect(
                $this->getSelectPart(
                    $aliases,
                    $associationName . '_resolved_value',
                    $targetField,
                    [$query->getJoinAlias($rootAlias . '.' . $associationName) . '.' . $targetField]
                )
            );
            $config->offsetSetByPath(
                sprintf('[filters][columns][%s][data_name]', $associationName),
                $associationName . '_resolved_id'
            );
            $config->offsetSetByPath(
                sprintf('[sorters][columns][%s][data_name]', $associationName),
                $associationName . '_resolved_value'
            );
            $config->offsetSetByPath(sprintf('[columns][%s][type]', $associationName), 'twig');
            $config->offsetSetByPath(sprintf('[columns][%s][frontend_type]', $associationName), 'html');
            $config->offsetSetByPath(
                sprintf('[columns][%s][template]', $associationName),
                'OroPaymentTermBundle:PaymentTerm:column.html.twig'
            );
        }
    }

    /**
     * @param array $aliases
     * @param string $fieldName
     * @param string $alias
     * @param array $prepend
     * @return string
     */
    protected function getSelectPart(array $aliases, $alias, $fieldName, array $prepend = [])
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

    /**
     * @param BuildBefore $event
     * @return array
     */
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

        return \array_intersect($associationNames, $selectedFields);
    }
}
