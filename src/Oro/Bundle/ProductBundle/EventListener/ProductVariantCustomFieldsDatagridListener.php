<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\EventListener\RowSelectionListener;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;
use Oro\Bundle\ProductBundle\Provider\VariantFieldProvider;
use Oro\Bundle\SecurityBundle\Form\FieldAclHelper;

/**
 * Configures datagrid of product variants.
 */
class ProductVariantCustomFieldsDatagridListener
{
    public const FORM_SELECTED_VARIANTS = 'selectedVariantFields';
    public const FORM_APPEND_VARIANTS = 'appendVariants';
    public const GRID_DYNAMIC_LOAD_OPTION = 'gridDynamicLoad';
    public const ATTRIBUTE_FAMILY = 'attributeFamily';

    public function __construct(
        private DoctrineHelper $doctrineHelper,
        private CustomFieldProvider $customFieldProvider,
        private VariantFieldProvider $variantFieldProvider,
        private FieldAclHelper $fieldAclHelper,
        private string $productClass,
        private string $productVariantLinkClass
    ) {
    }

    /**
     * Add restriction to show only products that have all variant fields values set
     */
    public function onBuildBeforeHideUnsuitable(BuildBefore $event)
    {
        $parameters = $event->getDatagrid()->getParameters();
        if (!$parameters->has('parentProduct')) {
            return;
        }

        $parentProductId = $parameters->get('parentProduct');
        $additionalParams = $parameters->get(ParameterBag::ADDITIONAL_PARAMETERS, []);
        $variantFields = [];

        if ($parentProductId) {
            /** @var Product $parentProduct */
            $parentProduct = $this->getProductRepository()->find($parentProductId);
            if (!$parentProduct) {
                throw new \InvalidArgumentException(
                    sprintf('Can not find parent product with id "%d"', $parentProductId)
                );
            }
            $variantFields = $this->getConfigurableAttributes(
                $parentProduct->getVariantFields(),
                $additionalParams
            );
        } elseif (isset($additionalParams[self::FORM_SELECTED_VARIANTS])) {
            $variantFields = $additionalParams[self::FORM_SELECTED_VARIANTS];
        }

        $appendVariants = $this->getMergedVariants($parameters);

        $config = $event->getConfig();
        $query = $config->getOrmQuery();

        $rootEntityAlias = $this->getRootAlias($config);

        // Always show selected product variants
        if ($appendVariants) {
            $query->addOrWhere(
                sprintf('%s.id IN (%s)', $rootEntityAlias, implode(',', $appendVariants))
            );
        }

        if ($variantFields) {
            $query->addAndWhere($this->getVariantAndWhereParts($variantFields, $rootEntityAlias));
        } else {
            $query->addAndWhere('1 = 0');
        }

        // Show all linked variants
        $variantLinkLeftJoin = $this->getVariantLinkLeftJoin($config);
        $query->addOrWhere(sprintf('%s.id IS NOT NULL', $variantLinkLeftJoin['alias']));
    }

    private function getVariantAndWhereParts(array $variantFields, string $rootEntityAlias): array
    {
        $customFieldsData = $this->customFieldProvider->getEntityCustomFields($this->productClass);
        $variantAndWherePart = [];
        foreach ($variantFields as $variantFieldName) {
            if (isset($customFieldsData[$variantFieldName]['type'])
                && ExtendHelper::isEnumerableType($customFieldsData[$variantFieldName]['type'])) {
                $variantAndWherePart[] = sprintf(
                    'JSON_EXTRACT(%s.serialized_data, \'%s\') IS NOT NULL',
                    $rootEntityAlias,
                    $variantFieldName
                );
            } else {
                $variantAndWherePart[] = sprintf('%s.%s IS NOT NULL', $rootEntityAlias, $variantFieldName);
            }
        }

        return $variantAndWherePart;
    }

    private function removeExtendFields(BuildAfter $event, array  $variantFields)
    {
        $datagridConfig = $event->getDatagrid()->getConfig();

        $allCustomFields = $this->customFieldProvider->getEntityCustomFields($this->productClass);

        foreach ($allCustomFields as $customField) {
            $customFieldName = $customField['name'];
            if (in_array($customFieldName, $variantFields, true)) {
                continue;
            }

            $datagridConfig->removeColumn($customFieldName);
        }
    }

    private function disableIsVariantColumn(DatagridConfiguration $config, Product $product, string $className): void
    {
        $path = '[columns][isVariant]';
        if ($this->fieldAclHelper->isFieldAclEnabled($className) && $config->offsetExistByPath($path)) {
            $fieldConfig = $config->offsetGetByPath($path);
            $fieldConfig['editable'] = $this->fieldAclHelper->isFieldModificationGranted($product, 'variantLinks');
            $config->offsetSetByPath($path, $fieldConfig);
        }
    }

    public function onBuildAfterEditGrid(BuildAfter $event)
    {
        $productRepository = $this->getProductRepository();
        $parameters = $event->getDatagrid()->getParameters();
        $config = $event->getDatagrid()->getConfig();
        $familyId = $parameters->get(self::ATTRIBUTE_FAMILY);
        $className = $config->getExtendedEntityClassName();
        /** @var AttributeFamily $attributeFamily */
        $attributeFamily = $this->doctrineHelper->getEntityRepository(AttributeFamily::class)->find($familyId);
        $product = $productRepository->find($event->getDatagrid()->getParameters()->get('parentProduct'));

        if ($attributeFamily) {
            $variantFields = $this->variantFieldProvider->getVariantFields($attributeFamily);
            $variantFields = array_keys($variantFields);
            $this->removeExtendFields($event, $variantFields);
            if ($product) {
                $this->disableIsVariantColumn($config, $product, $className);
            }
        }
    }

    public function onBuildAfter(BuildAfter $event)
    {
        $productRepository = $this->getProductRepository();

        /** @var Product $parentProduct */
        $parentProduct = $productRepository->find($event->getDatagrid()->getParameters()->get('parentProduct'));

        $parameters = $event->getDatagrid()->getParameters();

        $variantFields = $this->getConfigurableAttributes(
            $parentProduct->getVariantFields(),
            $parameters->get(ParameterBag::ADDITIONAL_PARAMETERS, [])
        );

        $this->removeExtendFields($event, $variantFields);
    }

    /**
     * @param array $productVariantFields
     * @param array $dynamicGridParams
     * @return array
     */
    private function getConfigurableAttributes(array $productVariantFields, array $dynamicGridParams)
    {
        if (array_key_exists(self::FORM_SELECTED_VARIANTS, $dynamicGridParams)) {
            $productVariantFields = !empty($dynamicGridParams[self::FORM_SELECTED_VARIANTS])
                ? $dynamicGridParams[self::FORM_SELECTED_VARIANTS]
                : [];
        }

        return $productVariantFields;
    }

    /**
     * @return EntityRepository
     */
    private function getProductRepository()
    {
        return $this->doctrineHelper->getEntityRepository($this->productClass);
    }

    /**
     * @param DatagridConfiguration $config
     * @return string
     */
    private function getRootAlias(DatagridConfiguration $config)
    {
        $rootAlias = $config->getOrmQuery()->getRootAlias();
        if (!$rootAlias) {
            throw new \InvalidArgumentException(
                sprintf(
                    'A root entity is missing for grid "%s"',
                    $config->getName()
                )
            );
        }

        return $rootAlias;
    }

    /**
     * @param DatagridConfiguration $config
     * @return array
     */
    private function getVariantLinkLeftJoin(DatagridConfiguration $config)
    {
        $result = null;

        $leftJoins = $config->getOrmQuery()->getLeftJoins();
        foreach ($leftJoins as $leftJoin) {
            if ($leftJoin['join'] === $this->productVariantLinkClass) {
                $result = $leftJoin;
            }
        }

        if (null === $result) {
            throw new \InvalidArgumentException(
                sprintf(
                    'A left join with "%s" is missing for grid "%s"',
                    $this->productVariantLinkClass,
                    $config->getName()
                )
            );
        }

        return $result;
    }

    /**
     * @param ParameterBag $parameters
     * @return array
     */
    private function getMergedVariants(ParameterBag $parameters)
    {
        $additionalParams = $parameters->get(ParameterBag::ADDITIONAL_PARAMETERS, []);

        $gridDynamicLoad = (bool)$this->extractAdditionalParams(
            $additionalParams,
            self::GRID_DYNAMIC_LOAD_OPTION,
            false
        );
        if ($gridDynamicLoad) {
            return $this->extractAdditionalParams($additionalParams, RowSelectionListener::GRID_PARAM_DATA_IN, []);
        } else {
            return $this->extractParameters($parameters, self::FORM_APPEND_VARIANTS);
        }
    }

    /**
     * @param ParameterBag $parameterBag
     * @param string $name
     * @return array
     */
    private function extractParameters(ParameterBag $parameterBag, $name)
    {
        $param = $parameterBag->get($name);
        if ($param === null) {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $param)));
    }

    /**
     * @param array $additionalParams
     * @param string $name
     * @param mixed|null $default
     * @return mixed|null
     */
    private function extractAdditionalParams(array $additionalParams, $name, $default = null)
    {
        return array_key_exists($name, $additionalParams) ? $additionalParams[$name] : $default;
    }
}
