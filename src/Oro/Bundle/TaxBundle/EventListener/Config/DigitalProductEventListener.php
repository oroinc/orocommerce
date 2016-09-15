<?php

namespace Oro\Bundle\TaxBundle\EventListener\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\DependencyInjection\OroTaxExtension;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\AbstractTaxCodeRepository;

class DigitalProductEventListener
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $settingsKey;

    /**
     * @var string
     */
    protected $taxCodeClass;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param string $taxCodeClass
     * @param string $settingsKey
     */
    public function __construct(DoctrineHelper $doctrineHelper, $taxCodeClass, $settingsKey)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->taxCodeClass = (string)$taxCodeClass;
        $this->settingsKey = (string)$settingsKey;
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function formPreSet(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();

        $key = OroTaxExtension::ALIAS . ConfigManager::SECTION_VIEW_SEPARATOR . $this->settingsKey;
        if (!array_key_exists($key, $settings)) {
            return;
        }

        $result = [];
        $codes = $settings[$key]['value'];
        if ($codes) {
            /** @var AbstractTaxCodeRepository $repository */
            $repository = $this->doctrineHelper->getEntityRepository($this->taxCodeClass);

            $result = $repository->findByCodes($this->filterCodes($codes));
        }

        $settings[$key]['value'] = $result;
        $event->setSettings($settings);
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function beforeSave(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();

        $key = OroTaxExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $this->settingsKey;
        if (!array_key_exists($key, $settings)) {
            return;
        }

        $result = [];
        $ids = (array)$settings[$key]['value'];

        if ($ids) {
            $taxCodes = $this->doctrineHelper->getEntityRepository($this->taxCodeClass)
                ->findBy(['id' => $this->filterIds($ids)]);

            $result = array_map(
                function (AbstractTaxCode $taxCode) {
                    return $taxCode->getCode();
                },
                $taxCodes
            );
        }

        $settings[$key]['value'] = $result;
        $event->setSettings($settings);
    }

    /**
     * @param array $data
     * @return array
     */
    protected function filterIds(array $data = [])
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

    /**
     * @param array $data
     * @return array
     */
    protected function filterCodes(array $data = [])
    {
        $data = array_filter(
            $data,
            function ($value) {
                return is_string($value) && $value;
            }
        );

        return array_values($data);
    }
}
