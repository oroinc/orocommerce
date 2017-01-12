<?php

namespace Oro\Bundle\CustomerBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\PreBuild;

class CustomerUserDatagridListener
{
    const NEW_ACCOUNT_KEY = 'newCustomer';
    const CHANGE_ACCOUNT_KEY = 'changeCustomerAction';
    const ACCOUNT_KEY = 'customer';
    const ROLE_KEY = 'role';

    const USER_SELECT_PART = 'user.id IN (:data_in) AND user.id NOT IN (:data_not_in)';
    const ROLE_SELECT_PART = '(:role MEMBER OF user.roles OR user.id IN (:data_in)) AND user.id NOT IN (:data_not_in)';
    const HAS_ROLE_SELECT = '(CASE WHEN %s THEN true ELSE false END) as hasRole';

    const ACCOUNT_CONDITION = 'user.customer = :customer';
    const NEW_ACCOUNT_CONDITION = 'user.customer = :newCustomer';

    /**
     * @param PreBuild $event
     */
    public function onBuildBefore(PreBuild $event)
    {
        $this->applyCustomerFilters($event->getConfig(), $event->getParameters());
    }

    /**
     * @param DatagridConfiguration $config
     * @param ParameterBag $parameters
     */
    protected function applyCustomerFilters(DatagridConfiguration $config, ParameterBag $parameters)
    {
        $query = $config->getOrmQuery();

        $condition = self::USER_SELECT_PART;
        $role = $parameters->get(self::ROLE_KEY);
        if ($role) {
            $condition = self::ROLE_SELECT_PART;
            $config->offsetAddToArrayByPath(
                DatagridConfiguration::DATASOURCE_BIND_PARAMETERS_PATH,
                [self::ROLE_KEY]
            );
        }

        $query->addSelect(sprintf(self::HAS_ROLE_SELECT, $condition));

        $additionalParameters = $parameters->get(ParameterBag::ADDITIONAL_PARAMETERS, []);
        if (!is_array($additionalParameters)) {
            return;
        }

        $additionalParameters = new ParameterBag($additionalParameters);

        $changeCustomerAction = $additionalParameters->get(self::CHANGE_ACCOUNT_KEY, false);
        $customer = $parameters->get(self::ACCOUNT_KEY);
        $newCustomer = $additionalParameters->get(self::NEW_ACCOUNT_KEY);

        if (!$changeCustomerAction && $customer) {
            $query->addOrWhere(self::ACCOUNT_CONDITION);
            $config->offsetAddToArrayByPath(
                DatagridConfiguration::DATASOURCE_BIND_PARAMETERS_PATH,
                ['customer']
            );
        } elseif ($changeCustomerAction && $newCustomer) {
            $query->addOrWhere(self::NEW_ACCOUNT_CONDITION);
            $config->offsetAddToArrayByPath(
                DatagridConfiguration::DATASOURCE_BIND_PARAMETERS_PATH,
                [
                    [
                        'name' => self::NEW_ACCOUNT_KEY,
                        'path' => sprintf('%s.%s', ParameterBag::ADDITIONAL_PARAMETERS, self::NEW_ACCOUNT_KEY),
                    ],
                ]
            );
        }
    }
}
