<?php

namespace Oro\Bundle\TaxBundle\EventSubscriber;

use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\ImportExportBundle\Event\AfterEntityPageLoadedEvent;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\LoadEntityRulesAndBackendHeadersEvent;
use Oro\Bundle\ImportExportBundle\Event\LoadTemplateFixturesEvent;
use Oro\Bundle\ImportExportBundle\Event\NormalizeEntityEvent;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Helper\CustomerTaxCodeImportExportHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomerTaxCodeImportExportSubscriber implements EventSubscriberInterface
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

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
        TranslatorInterface $translator,
        CustomerTaxCodeImportExportHelper $customerTaxManager,
        $customerClassName
    ) {
        $this->translator = $translator;
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
            StrategyEvent::PROCESS_AFTER => ['afterImportStrategy', -255],
            Events::AFTER_LOAD_TEMPLATE_FIXTURES => 'addTaxCodeToCustomers',
        ];
    }

    public function updateEntityResults(AfterEntityPageLoadedEvent $event)
    {
        $rows = $event->getRows();
        if (empty($rows) || !is_a($rows[0], $this->customerClassName)) {
            return;
        }

        $this->customerTaxCodes = $this->customerTaxCodeImportExportHelper->loadCustomerTaxCode($event->getRows());
    }

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

    public function afterImportStrategy(StrategyEvent $event)
    {
        /** @var Customer $entity */
        $entity = $event->getEntity();
        $data = $event->getContext()->getValue('itemData');

        if (!is_a($entity, $this->customerClassName)) {
            return;
        }

        try {
            $taxCode = $this->customerTaxCodeImportExportHelper->denormalizeCustomerTaxCode($data);

            if ($taxCode === null) {
                return;
            }
            $entity->setTaxCode($taxCode);
        } catch (EntityNotFoundException $e) {
            $event->setEntity(null);
            $event->getContext()->addError(
                $this->translator->trans(
                    'oro.tax.import_export.tax_code_doesnt_exist',
                    ['%customer_name%' => $entity->getName()]
                )
            );
        }
    }

    public function addTaxCodeToCustomers(LoadTemplateFixturesEvent $event)
    {
        foreach ($event->getEntities() as $customerData) {
            foreach ($customerData as $customer) {
                /** @var Customer $customer */
                $customer = $customer['entity'];

                if (!$customer instanceof Customer) {
                    continue;
                }

                $this->customerTaxCodes[$customer->getId()] = (new CustomerTaxCode())->setCode('Tax_code_1');
            }
        }
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
