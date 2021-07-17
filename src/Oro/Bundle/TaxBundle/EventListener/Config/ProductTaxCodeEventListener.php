<?php

namespace Oro\Bundle\TaxBundle\EventListener\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\TaxBundle\DependencyInjection\OroTaxExtension;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\AbstractTaxCodeRepository;

/**
 * Manages Tax shipping form in system configuration
 */
class ProductTaxCodeEventListener
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var TokenAccessorInterface
     */
    protected $tokenAccessor;

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
     * @param TokenAccessorInterface $tokenAccessor
     * @param string $taxCodeClass
     * @param string $settingsKey
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        TokenAccessorInterface $tokenAccessor,
        $taxCodeClass,
        $settingsKey
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->tokenAccessor = $tokenAccessor;
        $this->taxCodeClass = (string)$taxCodeClass;
        $this->settingsKey = (string)$settingsKey;
    }

    public function formPreSet(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();

        $key = OroTaxExtension::ALIAS . ConfigManager::SECTION_VIEW_SEPARATOR . $this->settingsKey;
        if (!array_key_exists($key, $settings)) {
            return;
        }

        $organization = $this->tokenAccessor->getOrganization();

        $result = [];
        $codes = $settings[$key]['value'];
        if ($codes) {
            /** @var AbstractTaxCodeRepository $repository */
            $repository = $this->doctrineHelper->getEntityRepository($this->taxCodeClass);
            $result = $repository->findByCodes($this->filterCodes($codes), $organization);
        }

        $settings[$key]['value'] = $result;
        $event->setSettings($settings);
    }

    public function beforeSave(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();

        if (!array_key_exists('value', $settings)) {
            return;
        }

        $result = [];
        $ids = (array)$settings['value'];

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

        $settings['value'] = $result;
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
