<?php

namespace Oro\Bundle\TaxBundle\EventSubscriber;

use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\Event\AfterEntityPageLoadedEvent;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\LoadEntityRulesAndBackendHeadersEvent;
use Oro\Bundle\ImportExportBundle\Event\LoadTemplateFixturesEvent;
use Oro\Bundle\ImportExportBundle\Event\NormalizeEntityEvent;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\ImportExportBundle\EventListener\ImportExportHeaderModifier;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Helper\CustomerTaxCodeImportExportHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Import/export event subscriber for customer tax codes.
 */
class CustomerTaxCodeImportExportSubscriber implements EventSubscriberInterface
{
    protected TranslatorInterface $translator;

    private CustomerTaxCodeImportExportHelper $customerTaxCodeImportExportHelper;

    private string $customerClassName;

    protected FieldHelper $fieldHelper;

    private array $customerTaxCodes = [];

    public function __construct(
        TranslatorInterface $translator,
        CustomerTaxCodeImportExportHelper $customerTaxManager,
        FieldHelper $fieldHelper,
        string $customerClassName
    ) {
        $this->translator = $translator;
        $this->customerTaxCodeImportExportHelper = $customerTaxManager;
        $this->fieldHelper = $fieldHelper;
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

        if (!$this->isEnable()) {
            return;
        }

        $this->customerTaxCodes += $this->customerTaxCodeImportExportHelper->loadNormalizedCustomerTaxCodes($rows);
    }

    public function normalizeEntity(NormalizeEntityEvent $event)
    {
        if (!$event->isFullData() || !is_a($event->getObject(), $this->customerClassName)) {
            return;
        }

        if (!$this->isEnable()) {
            return;
        }

        /** @var Customer $customer */
        $customer = $event->getObject();
        $event->setResultFieldValue('tax_code', $this->getCustomerTaxCode($customer));
    }

    public function loadEntityRulesAndBackendHeaders(LoadEntityRulesAndBackendHeadersEvent $event)
    {
        if (!$event->isFullData() || $event->getEntityName() !== $this->customerClassName) {
            return;
        }

        if (!$this->isEnable()) {
            return;
        }

        ImportExportHeaderModifier::addHeader(
            $event,
            sprintf('tax_code%scode', $event->getConvertDelimiter()),
            'Tax code',
            200
        );
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

                $customerTaxCode = (new CustomerTaxCode())->setCode('Tax_code_1');
                $normalizedCode = $this->customerTaxCodeImportExportHelper->normalizeCustomerTaxCode($customerTaxCode);
                $this->customerTaxCodes[$customer->getId()] = $normalizedCode;
            }
        }
    }


    /**
     * There is one issue that read of EntityReader will trigger pagination before the last item be processed.
     * So we need to keep all customer tax codes info in local cache and only reset after fetched.
     */
    private function getCustomerTaxCode(Customer $customer): ?array
    {
        if (!isset($this->customerTaxCodes[$customer->getId()])) {
            return null;
        }

        $result = $this->customerTaxCodes[$customer->getId()];
        unset($this->customerTaxCodes[$customer->getId()]);

        return $result;
    }

    /**
     * Do not act when customer class has entity config about this field to prevent duplicates
     */
    protected function isEnable(): bool
    {
        return $this->fieldHelper->getConfigValue($this->customerClassName, 'taxCode', 'excluded') !== false;
    }
}
