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

    protected $paymentTermEntityClass;

    /** @var  DeleteMessageTextGenerator */
    protected $deleteMessageGenerator;

    public function setPaymentTermClass($paymentTermEntityClass)
    {
        $this->paymentTermEntityClass = $paymentTermEntityClass;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBeforeCustomers(BuildBefore $event)
    {
        $this->addPaymentTermRelation(
            $event->getConfig(),
            'customer MEMBER OF ' . static::PAYMENT_TERM_ALIAS . '.customers'
        );

        $this->addPaymentTermGroupToCustomer($event->getConfig());

    }

    public function onBuildBeforePaymentTerm(OrmResultAfter $event)
    {
        $test = 4;
        $test = 4;

//        $deleteMessage = $this->deleteMessageGenerator->getDeleteMessageText()
//        $this->addConfigElement(, '[columns]', $column, static::PAYMENT_TERM_LABEL_ALIAS );
    }

    public function setDeleteMessageGenerator(DeleteMessageTextGenerator $deleteMessageGenerator)
    {
        $this->deleteMessageGenerator = $deleteMessageGenerator;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBeforeCustomerGroups(BuildBefore $event)
    {
        $this->addPaymentTermRelation(
            $event->getConfig(),
            'customer_group MEMBER OF ' . static::PAYMENT_TERM_ALIAS . '.customerGroups'
        );
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $joinCondition
     */
    protected function addPaymentTermRelation(DatagridConfiguration $config, $joinCondition)
    {

        $select = static::PAYMENT_TERM_ALIAS . '.label as ' . static::PAYMENT_TERM_LABEL_ALIAS;
        $this->addConfigElement($config, '[source][query][select]', $select);



        $leftJoin = [
            'join' => $this->paymentTermEntityClass,
            'alias' => static::PAYMENT_TERM_ALIAS,
            'conditionType' => 'WITH',
            'condition' => $joinCondition
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

    private function addPaymentTermGroupToCustomer($config)
    {
        $selectGroupPaymentTermLabel = static::PAYMENT_TERM_GROUP_ALIAS . '.label as ' . static::PAYMENT_TERM_GROUP_LABEL_ALIAS;
        $this->addConfigElement($config, '[source][query][select]', $selectGroupPaymentTermLabel);


//        if (isset($filters[static::PAYMENT_TERM_LABEL_ALIAS])) {
//            $value = $filters[static::PAYMENT_TERM_LABEL_ALIAS]['value'];
//
//            $whereCondition = static::PAYMENT_TERM_GROUP_ALIAS . '.id IN (' . $value . ')';
//            $this->addConfigElement($config, '[source][query][where][or]', $whereCondition, 5);
//        }

        $leftJoin = [
            'join' => $this->paymentTermEntityClass,
            'alias' => static::PAYMENT_TERM_GROUP_ALIAS,
            'conditionType' => 'WITH',
            'condition' => 'customer.group MEMBER OF ' . static::PAYMENT_TERM_GROUP_ALIAS . '.customerGroups'
        ];
        $this->addConfigElement($config, '[source][query][join][left]', $leftJoin);

        $column = [
            'type' => 'twig',
            'label' => 'orob2b.payment.paymentterm.entity_label',
            'frontend_type' => 'html',
            'template' => 'OroB2BPaymentBundle:Customer:Datagrid/Property/paymentTerm.html.twig'
        ];

        $this->addConfigElement($config, '[columns]', $column, static::PAYMENT_TERM_LABEL_ALIAS );
    }
}
