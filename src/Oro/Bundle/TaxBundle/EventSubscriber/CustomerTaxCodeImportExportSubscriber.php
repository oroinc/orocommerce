<?php

namespace Oro\Bundle\TaxBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\ImportExportBundle\Event\AfterEntityPageLoadedEvent;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\NormalizeEntityEvent;
use Oro\Bundle\ImportExportBundle\Event\LoadEntityRulesAndBackendHeadersEvent;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Helper\CustomerTaxCodeImportExportHelper;

class CustomerTaxCodeImportExportSubscriber implements EventSubscriberInterface
{
    /**
     * @var CustomerTaxCodeImportExportHelper
     */
    private $customerTaxCodeImportExportHelper;

    /**
     * @var string
     */
    private $customerClassName;

    /**
     * @var CustomerTaxCode[]
     */
    private $customerTaxCodes = [];

    /**
     * @param CustomerTaxCodeImportExportHelper $customerTaxManager
     * @param string $customerClassName
     */
    public function __construct(
        CustomerTaxCodeImportExportHelper $customerTaxManager,
        $customerClassName
    ) {
        $this->customerTaxCodeImportExportHelper = $customerTaxManager;
        $this->customerClassName = $customerClassName;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::AFTER_ENTITY_PAGE_LOADED => 'updateEntityResults',
            Events::AFTER_NORMALIZE_ENTITY => 'normalizeEntity',
            Events::AFTER_LOAD_ENTITY_RULES_AND_BACKEND_HEADERS => 'loadEntityRulesAndBackendHeaders',
        ];
    }

    /**
     * @param AfterEntityPageLoadedEvent $event
     */
    public function updateEntityResults(AfterEntityPageLoadedEvent $event)
    {
        $rows = $event->getRows();
        if (empty($rows) || !is_a($rows[0], $this->customerClassName)) {
            return;
        }

        $this->customerTaxCodes = $this->customerTaxCodeImportExportHelper->loadCustomerTaxCode($event->getRows());
    }

    /**
     * @param NormalizeEntityEvent $event
     */
    public function normalizeEntity(NormalizeEntityEvent $event)
    {
        if (!$event->isFullData() || !is_a($event->getObject(), $this->customerClassName)) {
            return;
        }

        /** @var Customer $customer */
        $customer = $event->getObject();
        $event->setResultField(
            'tax_code',
            $this->customerTaxCodeImportExportHelper->normalizeCustomerTaxCode(
                $this->getCustomerTaxCode($customer)
            )
        );
    }

    /**
     * @param LoadEntityRulesAndBackendHeadersEvent $event
     */
    public function loadEntityRulesAndBackendHeaders(LoadEntityRulesAndBackendHeadersEvent $event)
    {
        if (!$event->isFullData() || $event->getEntityName() !== $this->customerClassName) {
            return;
        }

        $event->addHeader([
            'value' => sprintf('tax_code%scode', $event->getConvertDelimiter()),
            'order' => 200,
        ]);

        $event->setRule('Tax code', [
            'value' => sprintf('tax_code%scode', $event->getConvertDelimiter()),
            'order' => 200,
        ]);
    }


    /**
     * @param Customer $customer
     * @return CustomerTaxCode
     */
    private function getCustomerTaxCode(Customer $customer)
    {
        if (!isset($this->customerTaxCodes[$customer->getId()])) {
            return null;
        }

        return $this->customerTaxCodes[$customer->getId()];
    }
}
