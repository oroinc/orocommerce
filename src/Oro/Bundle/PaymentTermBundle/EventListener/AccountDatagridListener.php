<?php

namespace Oro\Bundle\PaymentTermBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityExtendBundle\Grid\DynamicFieldsExtension;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;

class AccountDatagridListener
{
    /** @var PaymentTermAssociationProvider */
    private $paymentTermAssociationProvider;

    /**
     * @param PaymentTermAssociationProvider $paymentTermAssociationProvider
     */
    public function __construct(PaymentTermAssociationProvider $paymentTermAssociationProvider)
    {
        $this->paymentTermAssociationProvider = $paymentTermAssociationProvider;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $className = $config->offsetGetByPath(DynamicFieldsExtension::EXTEND_ENTITY_CONFIG_PATH);
        if (!is_a(Account::class, $className, true)) {
            return;
        }

        $associationNames = $this->paymentTermAssociationProvider->getAssociationNames($className);
        if (!$associationNames) {
            return;
        }

        $accountGroupAssociationNames = $this->paymentTermAssociationProvider->getAssociationNames(AccountGroup::class);
        if (!$accountGroupAssociationNames) {
            return;
        }

        $from = $config->offsetGetByPath('[source][query][from]');
        $root = reset($from);

        $aliases = [];
        foreach ($accountGroupAssociationNames as $accountGroupAssociationName) {
            $alias = 'agpt_'.$accountGroupAssociationName;
            $aliases[] = $alias;
            $config->offsetAddToArrayByPath(
                '[source][query][join][left]',
                [
                    [
                        'join' => 'account_group.'.$accountGroupAssociationName,
                        'alias' => $alias,
                    ],
                ]
            );
        }

        $config->offsetAddToArrayByPath(
            '[source][query][select]',
            [$this->getSelectPart($aliases, 'account_group_payment_term', 'label')]
        );

        foreach ($associationNames as $associationName) {
            $config->offsetAddToArrayByPath(
                '[source][query][select]',
                [
                    $this->getSelectPart(
                        $aliases,
                        $associationName.'_resolved_id',
                        'id',
                        [sprintf('IDENTITY(%s.%s)', $root['alias'], $associationName)]
                    ),
                ]
            );

            $targetField = $this->paymentTermAssociationProvider->getTargetField(Account::class, $associationName);
            $config->offsetAddToArrayByPath(
                '[source][query][select]',
                [
                    $this->getSelectPart(
                        $aliases,
                        $associationName.'_resolved_value',
                        $targetField,
                        [$associationName.'.'.$targetField]
                    ),
                ]
            );
            $config->offsetSetByPath(
                sprintf('[filters][columns][%s][data_name]', $associationName),
                $associationName.'_resolved_id'
            );
            $config->offsetSetByPath(
                sprintf('[sorters][columns][%s][data_name]', $associationName),
                $associationName.'_resolved_value'
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
                            return $alias.'.'.$fieldName;
                        },
                        $aliases
                    )
                )
            ),
            $alias
        );
    }
}
