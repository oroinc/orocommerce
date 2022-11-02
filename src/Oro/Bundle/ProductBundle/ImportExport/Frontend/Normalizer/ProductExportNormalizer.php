<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Frontend\Normalizer;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Frontend\Configuration\ProductImportExportConfigurationProvider;
use Oro\Bundle\ProductBundle\ImportExport\Frontend\DataConverter\ProductExportDataConverter;
use Oro\Bundle\ProductBundle\ImportExport\Frontend\Event\ProductExportNormalizerEvent;

/**
 * Product normalizer used for product listing export.
 * Encapsulates logic for specific Product export with specific requirements so its priority should be always higher
 * than common normalizers for Product Entity.
 */
class ProductExportNormalizer extends ConfigurableEntityNormalizer
{
    protected ConfigProvider $configProvider;

    protected LocalizationHelper $localizationHelper;

    protected ManagerRegistry $managerRegistry;

    protected ?Localization $currentLocalization = null;

    protected bool $localizedFields = false;

    public function __construct(
        FieldHelper $fieldHelper,
        ConfigProvider $configProvider,
        LocalizationHelper $localizationHelper,
        ManagerRegistry $managerRegistry
    ) {
        parent::__construct($fieldHelper);

        $this->configProvider = $configProvider;
        $this->localizationHelper = $localizationHelper;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $result = parent::normalize($object, $format, $context);

        $result[ProductExportDataConverter::PRODUCT_NAME_FIELD] = $this->localizationHelper->getLocalizedValue(
            $object->getNames(),
            $this->getCurrentLocalization($context)
        );

        if ($this->dispatcher) {
            $event = new ProductExportNormalizerEvent($object, $result, $context);
            $this->dispatcher->dispatch($event, ProductExportNormalizerEvent::FRONTEND_PRODUCT_EXPORT_NORMALIZE);
            $result = $event->getData();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return is_a($data, Product::class)
            && isset($context['processorAlias'])
            && $context['processorAlias'] === ProductImportExportConfigurationProvider::EXPORT_PROCESSOR_ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return false;
    }

    /**
     * @param string $entityName
     * @param string $fieldName
     * @param array $context
     * @return bool
     */
    protected function isFieldSkippedForNormalization($entityName, $fieldName, array $context)
    {
        $config = $this->configProvider->getConfig($entityName, $fieldName);
        return $config->has('use_in_export') ? !$config->get('use_in_export', false, false) : true;
    }

    private function getCurrentLocalization(array $context): ?Localization
    {
        if (null === $this->currentLocalization) {
            $localization = null;
            $localizationId = $context['currentLocalizationId'] ?? null;

            if ($localizationId) {
                $localization = $this->managerRegistry->getRepository(Localization::class)
                    ->find($localizationId);
            }

            // If localization is not defined in export options try to get current localizations from user settings or
            // if it is empty default localization used.
            if (!$localization) {
                $localization = $this->localizationHelper->getCurrentLocalization();
            }

            if (!$localization) {
                throw new \LogicException('Current localization is not configured');
            }

            $this->currentLocalization = $localization;
        }

        return $this->currentLocalization;
    }
}
