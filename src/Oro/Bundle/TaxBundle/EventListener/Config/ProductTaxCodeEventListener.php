<?php

namespace Oro\Bundle\TaxBundle\EventListener\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TaxBundle\DependencyInjection\Configuration;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;

/**
 * Manages Tax shipping form in system configuration
 */
class ProductTaxCodeEventListener
{
    private DoctrineHelper $doctrineHelper;
    private AclHelper $aclHelper;
    private string $settingsKey;

    public function __construct(DoctrineHelper $doctrineHelper, AclHelper $aclHelper, string $settingsKey)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->aclHelper = $aclHelper;
        $this->settingsKey = $settingsKey;
    }

    public function formPreSet(ConfigSettingsUpdateEvent $event): void
    {
        $settings = $event->getSettings();

        $key = Configuration::ROOT_NODE . ConfigManager::SECTION_VIEW_SEPARATOR . $this->settingsKey;
        if (!array_key_exists($key, $settings)) {
            return;
        }

        $result = [];
        $codes = $settings[$key][ConfigManager::VALUE_KEY];
        if ($codes) {
            $qb = $this->doctrineHelper->createQueryBuilder(ProductTaxCode::class, 'taxCode');
            $qb
                ->where($qb->expr()->in('taxCode.code', ':codes'))
                ->setParameter('codes', $codes);

            $result = $this->aclHelper->apply($qb)->getResult();
        }

        $settings[$key][ConfigManager::VALUE_KEY] = $result;
        $event->setSettings($settings);
    }

    public function beforeSave(ConfigSettingsUpdateEvent $event): void
    {
        $settings = $event->getSettings();

        if (!array_key_exists(ConfigManager::VALUE_KEY, $settings)) {
            return;
        }

        $result = [];
        $ids = (array) $settings[ConfigManager::VALUE_KEY];

        if ($ids) {
            $taxCodes = $this->doctrineHelper->getEntityRepository(ProductTaxCode::class)
                ->findBy(['id' => $this->filterIds($ids)]);

            $result = array_map(
                function (AbstractTaxCode $taxCode) {
                    return $taxCode->getCode();
                },
                $taxCodes
            );
        }

        $settings[ConfigManager::VALUE_KEY] = $result;
        $event->setSettings($settings);
    }

    private function filterIds(array $data = []): array
    {
        $data = array_filter(
            $data,
            function ($value) {
                return false !== filter_var($value, FILTER_VALIDATE_INT);
            }
        );

        $data = array_map('intval', $data);

        return array_values($data);
    }
}
