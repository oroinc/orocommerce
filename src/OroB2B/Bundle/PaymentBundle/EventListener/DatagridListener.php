<?php

namespace OroB2B\Bundle\PaymentBundle\EventListener;

use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class DatagridListener
{
    const PAYMENT_TERM_LABEL_ALIAS = 'payment_term_label';
    const PAYMENT_TERM_ALIAS = 'payment_term';
    const PAYMENT_TERM_GROUP_LABEL_ALIAS = 'payment_term_group_label';
    const PAYMENT_TERM_GROUP_ALIAS = 'payment_term_group';
    const PAYMENT_TERM_FOR_FILTER = 'payment_term_for_filter';

    /** @var string $paymentTermEntityClass */
    protected $paymentTermEntityClass;

    /**
     * @param string $paymentTermEntityClass
     */
    public function setPaymentTermClass($paymentTermEntityClass)
    {
        $this->paymentTermEntityClass = $paymentTermEntityClass;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBeforeAccounts(BuildBefore $event)
    {
        $this->addPaymentTermRelationForAccount($event->getConfig());
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBeforeAccountGroups(BuildBefore $event)
    {
        $this->addPaymentTermRelationForAccountGroup($event->getConfig());
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function addPaymentTermRelationForAccount(DatagridConfiguration $config)
    {

        $selectAccountPaymentTerm = static::PAYMENT_TERM_ALIAS . '.label as ' . static::PAYMENT_TERM_LABEL_ALIAS;
        $this->addConfigElement($config, '[source][query][select]', $selectAccountPaymentTerm);

        $selectGroupPaymentTermLabel =
            static::PAYMENT_TERM_GROUP_ALIAS . '.label as ' . static::PAYMENT_TERM_GROUP_LABEL_ALIAS;
        $this->addConfigElement($config, '[source][query][select]', $selectGroupPaymentTermLabel);

        $selectPaymentTermForFilter = '(CASE WHEN ' .
            static::PAYMENT_TERM_ALIAS . '.id IS NOT NULL THEN ' .
            static::PAYMENT_TERM_ALIAS . '.id ELSE ' . static::PAYMENT_TERM_GROUP_ALIAS . '.id END) as ' .
            static::PAYMENT_TERM_FOR_FILTER;
        $this->addConfigElement($config, '[source][query][select]', $selectPaymentTermForFilter);

        $leftJoinPaymentTerm = [
            'join' => $this->paymentTermEntityClass,
            'alias' => static::PAYMENT_TERM_ALIAS,
            'conditionType' => Join::WITH,
            'condition' => 'account MEMBER OF ' . static::PAYMENT_TERM_ALIAS . '.accounts'
        ];
        $this->addConfigElement($config, '[source][query][join][left]', $leftJoinPaymentTerm);

        $leftJoinAccountGroupPaymentTerm = [
            'join' => $this->paymentTermEntityClass,
            'alias' => static::PAYMENT_TERM_GROUP_ALIAS,
            'conditionType' => Join::WITH,
            'condition' => 'account.group MEMBER OF ' . static::PAYMENT_TERM_GROUP_ALIAS . '.accountGroups'
        ];
        $this->addConfigElement($config, '[source][query][join][left]', $leftJoinAccountGroupPaymentTerm);

        $column = [
            'type' => 'twig',
            'label' => 'orob2b.payment.paymentterm.entity_label',
            'frontend_type' => 'html',
            'template' => 'OroB2BPaymentBundle:Account:Datagrid/Property/paymentTerm.html.twig'
        ];
        $this->addConfigElement($config, '[columns]', $column, static::PAYMENT_TERM_LABEL_ALIAS);

        $sorter = ['data_name' => static::PAYMENT_TERM_FOR_FILTER];
        $this->addConfigElement($config, '[sorters][columns]', $sorter, static::PAYMENT_TERM_LABEL_ALIAS);

        $filter = [
            'type' => 'entity',
            'data_name' => 'CAST(payment_term_for_filter as integer)',
            'options' => [
                'field_type' => 'entity',
                'field_options' => [
                    'class' => $this->paymentTermEntityClass,
                    'property' => 'label',
                ]
            ]
        ];
        $this->addConfigElement($config, '[filters][columns]', $filter, static::PAYMENT_TERM_LABEL_ALIAS);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function addPaymentTermRelationForAccountGroup(DatagridConfiguration $config)
    {

        $select = static::PAYMENT_TERM_ALIAS . '.label as ' . static::PAYMENT_TERM_LABEL_ALIAS;
        $this->addConfigElement($config, '[source][query][select]', $select);

        $leftJoin = [
            'join' => $this->paymentTermEntityClass,
            'alias' => static::PAYMENT_TERM_ALIAS,
            'conditionType' => Join::WITH,
            'condition' => 'account_group MEMBER OF ' . static::PAYMENT_TERM_ALIAS . '.accountGroups'
        ];
        $this->addConfigElement($config, '[source][query][join][left]', $leftJoin);

        // column
        $column = ['label' => 'orob2b.payment.paymentterm.entity_label'];
        $this->addConfigElement($config, '[columns]', $column, static::PAYMENT_TERM_LABEL_ALIAS);

        // sorter
        $sorter = ['data_name' => static::PAYMENT_TERM_LABEL_ALIAS];
        $this->addConfigElement($config, '[sorters][columns]', $sorter, static::PAYMENT_TERM_LABEL_ALIAS);

        // filter
        $filter = [
            'type' => 'entity',
            'data_name' => static::PAYMENT_TERM_ALIAS . '.id',
            'options' => [
                'field_type' => 'entity',
                'field_options' => [
                    'class' => $this->paymentTermEntityClass,
                    'property' => 'label',
                ]
            ]
        ];
        $this->addConfigElement($config, '[filters][columns]', $filter, static::PAYMENT_TERM_LABEL_ALIAS);
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $path
     * @param mixed $element
     * @param mixed $key
     */
    protected function addConfigElement(DatagridConfiguration $config, $path, $element, $key = null)
    {
        $select = $config->offsetGetByPath($path);
        if ($key) {
            $select[$key] = $element;
        } else {
            $select[] = $element;
        }
        $config->offsetSetByPath($path, $select);
    }
}
