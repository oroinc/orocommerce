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

/**
 * Product normalizer used for product listing export.
 * Incapsulates logic for specific Product export with specific requirements so its priority should be always higher
 * than common normalizers for Product Entity.
 */
class ProductExportNormalizer extends ConfigurableEntityNormalizer
{
    protected ConfigProvider $attributeConfigProvider;
    protected LocalizationHelper $localizationHelper;
    protected ManagerRegistry $managerRegistry;
    protected ?Localization $currentLocalization = null;
    protected bool $localizedFields = false;

    /**
     * @param FieldHelper $fieldHelper
     * @param ConfigProvider $attributeConfigProvider
     * @param LocalizationHelper $localizationHelper
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(
        FieldHelper $fieldHelper,
        ConfigProvider $attributeConfigProvider,
        LocalizationHelper $localizationHelper,
        ManagerRegistry $managerRegistry
    ) {
        parent::__construct($fieldHelper);

        $this->attributeConfigProvider = $attributeConfigProvider;
        $this->localizationHelper = $localizationHelper;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $result = parent::normalize($object, $format, $context);

        if (!array_key_exists(ProductExportDataConverter::PRODUCT_NAME_FIELD, $result)) {
            $result[ProductExportDataConverter::PRODUCT_NAME_FIELD] = $this->localizationHelper->getLocalizedValue(
                $object->getNames(),
                $this->getCurrentLocalization($context)
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return is_a($data, Product::class)
            && isset($context['processorAlias'])
            && $context['processorAlias'] === ProductImportExportConfigurationProvider::EXPORT_PROCESSOR_ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
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
        $config = $this->attributeConfigProvider->getConfig($entityName, $fieldName);
        return $config->has('use_in_export') ? !$config->get('use_in_export', false, false) : true;
    }

    /**
     * @param array $context
     * @return Localization|null
     */
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
