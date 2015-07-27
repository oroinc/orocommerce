<?php

namespace OroB2B\Bundle\PaymentBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use OroB2B\Bundle\PaymentBundle\Twig\DeleteMessageTextGenerator;

class DatagridListener
{
    const PAYMENT_TERM_LABEL_ALIAS = 'payment_term_label';
    const PAYMENT_TERM_ALIAS = 'payment_term';
    const PAYMENT_TERM_GROUP_LABEL_ALIAS = 'payment_term_group_label';
    const PAYMENT_TERM_GROUP_ALIAS = 'payment_term_group';
    const PAYMENT_TERM_FOR_FILTER = 'payment_term_for_filter';

    /** @var $paymentTermEntityClass */
    protected $paymentTermEntityClass;

    /**
     * @param $paymentTermEntityClass
     */
    public function setPaymentTermClass($paymentTermEntityClass)
    {
        $this->paymentTermEntityClass = $paymentTermEntityClass;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBeforeCustomers(BuildBefore $event)
    {
        $this->addPaymentTermRelationForCustomer($event->getConfig());
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBeforeCustomerGroups(BuildBefore $event)
    {
        $this->addPaymentTermRelationForCustomerGroup($event->getConfig());
    }

    /**
     * @param DatagridConfiguration $config
     */
    private function addPaymentTermRelationForCustomer(DatagridConfiguration $config)
    {

        $selectCustomerPaymentTerm = static::PAYMENT_TERM_ALIAS . '.label as ' . static::PAYMENT_TERM_LABEL_ALIAS;
        $this->addConfigElement($config, '[source][query][select]', $selectCustomerPaymentTerm);

        $selectGroupPaymentTermLabel =
            static::PAYMENT_TERM_GROUP_ALIAS . '.label as ' . static::PAYMENT_TERM_GROUP_LABEL_ALIAS;
        $this->addConfigElement($config, '[source][query][select]', $selectGroupPaymentTermLabel);

        $selectPaymentTermForFilter = "CONCAT(CASE WHEN " .
            static::PAYMENT_TERM_ALIAS . ".id IS NOT NULL THEN " .
            static::PAYMENT_TERM_ALIAS . ".id ELSE CASE WHEN " .
            static::PAYMENT_TERM_GROUP_ALIAS . ".id IS NOT NULL THEN " .
            static::PAYMENT_TERM_GROUP_ALIAS . ".id ELSE '' END END, '') as " . static::PAYMENT_TERM_FOR_FILTER;
        $this->addConfigElement($config, '[source][query][select]', $selectPaymentTermForFilter);

        $leftJoinPaymentTerm = [
            'join' => $this->paymentTermEntityClass,
            'alias' => static::PAYMENT_TERM_ALIAS,
            'conditionType' => 'WITH',
            'condition' => 'customer MEMBER OF ' . static::PAYMENT_TERM_ALIAS . '.customers'
        ];
        $this->addConfigElement($config, '[source][query][join][left]', $leftJoinPaymentTerm);


        $leftJoinCustomerGroupPaymentTerm = [
            'join' => $this->paymentTermEntityClass,
            'alias' => static::PAYMENT_TERM_GROUP_ALIAS,
            'conditionType' => 'WITH',
            'condition' => 'customer.group MEMBER OF ' . static::PAYMENT_TERM_GROUP_ALIAS . '.customerGroups'
        ];
        $this->addConfigElement($config, '[source][query][join][left]', $leftJoinCustomerGroupPaymentTerm);

        $column = [
            'type' => 'twig',
            'label' => 'orob2b.payment.paymentterm.entity_label',
            'frontend_type' => 'html',
            'template' => 'OroB2BPaymentBundle:Customer:Datagrid/Property/paymentTerm.html.twig'
        ];
        $this->addConfigElement($config, '[columns]', $column, static::PAYMENT_TERM_LABEL_ALIAS );

        $sorter = ['data_name' => static::PAYMENT_TERM_FOR_FILTER];
        $this->addConfigElement($config, '[sorters][columns]', $sorter, static::PAYMENT_TERM_LABEL_ALIAS);

        $filter = [
            'type' => 'entity',
            'data_name' => static::PAYMENT_TERM_FOR_FILTER,
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
     * @param string $joinCondition
     */
    protected function addPaymentTermRelationForCustomerGroup(DatagridConfiguration $config)
    {

        $select = static::PAYMENT_TERM_ALIAS . '.label as ' . static::PAYMENT_TERM_LABEL_ALIAS;
        $this->addConfigElement($config, '[source][query][select]', $select);

        $leftJoin = [
            'join' => $this->paymentTermEntityClass,
            'alias' => static::PAYMENT_TERM_ALIAS,
            'conditionType' => 'WITH',
            'condition' => 'customer_group MEMBER OF ' . static::PAYMENT_TERM_ALIAS . '.customerGroups'
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
