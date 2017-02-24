<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;

class ProductVariantCustomFieldsDatagridListener
{
    const FORM_SELECTED_VARIANTS = 'selectedVariantFields';

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var CustomFieldProvider
     */
    private $customFieldProvider;

    /**
     * @var string
     */
    private $productClass;

    /**
     * @var string
     */
    private $productVariantLinkClass;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param CustomFieldProvider $customFieldProvider
     * @param string $productClass
     * @param string $productVariantLinkClass
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        CustomFieldProvider $customFieldProvider,
        $productClass,
        $productVariantLinkClass
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->customFieldProvider = $customFieldProvider;
        $this->productClass = $productClass;
        $this->productVariantLinkClass = $productVariantLinkClass;
    }

    /**
     * Add restriction to show only products that have all variant fields values set
     *
     * @param BuildBefore $event
     */
    public function onBuildBeforeHideUnsuitable(BuildBefore $event)
    {
        $parameters = $event->getDatagrid()->getParameters();
        if (!$parameters->has('parentProduct')) {
            return;
        }

        $parentProductId = $parameters->get('parentProduct');

        /** @var Product $parentProduct */
        $parentProduct = $this->getProductRepository()->find($parentProductId);
        if (!$parentProduct) {
            return;
        }

        $config = $event->getConfig();
        $query = $config->getOrmQuery();

        $variantFields = $this->getVariantFields(
            $parentProduct->getVariantFields(),
            $parameters->get(ParameterBag::ADDITIONAL_PARAMETERS, [])
        );

        if ($variantFields) {
            $rootEntityAlias = $this->getRootAlias($config);

            $variantAndWherePart = [];
            foreach ($variantFields as $variantFieldName) {
                $variantAndWherePart[] = sprintf('%s.%s IS NOT NULL', $rootEntityAlias, $variantFieldName);
            }
            $query->addAndWhere($variantAndWherePart);
        } else {
            // Don't show any product variants if there are no variant fields specified in the configurable product
            $query->addAndWhere('1 = 0');
        }

        // Show all linked variants
        $variantLinkLeftJoin = $this->getVariantLinkLeftJoin($config);
        $query->addOrWhere(sprintf('%s.id IS NOT NULL', $variantLinkLeftJoin['alias']));
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagridConfig = $event->getDatagrid()->getConfig();
        $productRepository = $this->getProductRepository();

        /** @var Product $parentProduct */
        $parentProduct = $productRepository->find($event->getDatagrid()->getParameters()->get('parentProduct'));
        $allCustomFields = $this->customFieldProvider->getEntityCustomFields($this->productClass);
        $parameters = $event->getDatagrid()->getParameters();

        $variantFields = $this->getVariantFields(
            $parentProduct->getVariantFields(),
            $parameters->get(ParameterBag::ADDITIONAL_PARAMETERS, [])
        );

        foreach ($allCustomFields as $customField) {
            $customFieldName = $customField['name'];
            if (in_array($customFieldName, $variantFields, true)) {
                continue;
            }

            $datagridConfig->removeColumn($customFieldName);
        }
    }

    /**
     * @param array $productVariantFields
     * @param array $dynamicGridParams
     * @return array
     */
    private function getVariantFields(array $productVariantFields, array $dynamicGridParams)
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
}
