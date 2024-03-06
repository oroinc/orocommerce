<?php

namespace Oro\Bundle\CheckoutBundle\Datagrid;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmQueryConfiguration;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

/**
 * Adds additional configuration and metadata to the grid.
 */
class FrontendLineItemsGridExtension extends AbstractExtension
{
    private array $supportedGrids;
    private ManagerRegistry $doctrine;
    private ConfigManager $configManager;
    private CheckoutLineItemsProvider $checkoutLineItemsProvider;
    private array $cache = [];

    public function __construct(
        array $supportedGrids,
        ManagerRegistry $doctrine,
        ConfigManager $configManager,
        CheckoutLineItemsProvider $checkoutLineItemsProvider
    ) {
        $this->supportedGrids = $supportedGrids;
        $this->doctrine = $doctrine;
        $this->configManager = $configManager;
        $this->checkoutLineItemsProvider = $checkoutLineItemsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config): bool
    {
        return \in_array($config->getName(), $this->supportedGrids, true) && parent::isApplicable($config);
    }

    /**
     * {@inheritdoc}
     */
    public function setParameters(ParameterBag $parameters): void
    {
        if ($parameters->has(ParameterBag::MINIFIED_PARAMETERS)) {
            $minifiedParameters = $parameters->get(ParameterBag::MINIFIED_PARAMETERS);
            $additional = $parameters->get(ParameterBag::ADDITIONAL_PARAMETERS, []);
            if (\array_key_exists('g', $minifiedParameters)) {
                $additional['group'] = $minifiedParameters['g']['group'] ?? false;
            }
            $parameters->set(ParameterBag::ADDITIONAL_PARAMETERS, $additional);
        }

        parent::setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function processConfigs(DatagridConfiguration $config): void
    {
        $queryPart = 'lineItem.id';
        if ($this->isLineItemsGrouped()) {
            $queryPart = '(SELECT GROUP_CONCAT(innerItem.id ORDER BY innerItem.id ASC) ' .
                'FROM Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem innerItem ' .
                'WHERE innerItem.id NOT IN (:unacceptable_ids) ' .
                'AND (innerItem.parentProduct = lineItem.parentProduct OR innerItem.product = lineItem.product) ' .
                'AND innerItem.checkout = lineItem.checkout ' .
                'AND innerItem.productUnit = lineItem.productUnit) as allLineItemsIds';
        }
        $config->offsetAddToArrayByPath(OrmQueryConfiguration::SELECT_PATH, [$queryPart]);

        $checkoutId = $this->getCheckoutId();
        if (!$checkoutId) {
            return;
        }

        $checkout = $this->getCheckout($checkoutId);
        $orderLineItems = null !== $checkout
            ? $this->checkoutLineItemsProvider->getCheckoutLineItems($checkout)
            : new ArrayCollection([]);
        $maxLineItemsPerPage = $this->configManager->get('oro_checkout.checkout_max_line_items_per_page');
        if ($orderLineItems->count() <= $maxLineItemsPerPage) {
            $maxLineItemsPerPage = [
                'label' => 'oro.checkout.grid.toolbar.pageSize.all.label',
                'size' => $maxLineItemsPerPage
            ];
        }

        $config->offsetSetByPath(
            '[options][toolbarOptions][pageSize][items]',
            array_merge(
                $config->offsetGetByPath('[options][toolbarOptions][pageSize][items]'),
                [$maxLineItemsPerPage]
            )
        );

        $unacceptableIds = [];
        if (null !== $checkout) {
            $orderLineItemMap = [];
            foreach ($orderLineItems as $orderLineItem) {
                $orderLineItemMap[$orderLineItem->getId()] = true;
            }
            foreach ($checkout->getLineItems() as $lineItem) {
                $lineItemId = $lineItem->getId();
                if (!isset($orderLineItemMap[$lineItemId])) {
                    $unacceptableIds[] = $lineItemId;
                }
            }
        }
        $this->parameters->set('unacceptable_ids', $unacceptableIds);

        if ($this->parameters->get('acceptable_ids')) {
            $config->offsetAddToArrayByPath(
                OrmQueryConfiguration::WHERE_AND_PATH,
                ['lineItem.id IN (:acceptable_ids)']
            );
            $config->offsetAddToArrayByPath('[source][bind_parameters]', ['acceptable_ids']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data): void
    {
        $checkoutId = $this->getCheckoutId();
        if (!$checkoutId) {
            return;
        }

        $data->offsetSetByPath('[canBeGrouped]', $this->canBeGrouped($checkoutId));
        $data->offsetAddToArrayByPath('[initialState][parameters]', ['group' => false]);
        $data->offsetAddToArrayByPath('[state][parameters]', ['group' => $this->isLineItemsGrouped()]);
    }

    /**
     * {@inheritdoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result): void
    {
        $checkoutId = $this->getCheckoutId();
        if (!$checkoutId) {
            return;
        }

        $result->offsetAddToArrayByPath(
            '[metadata]',
            [
                'canBeGrouped' => $this->canBeGrouped($checkoutId)
            ]
        );
    }

    private function getCheckoutId(): int
    {
        return (int)$this->parameters->get('checkout_id');
    }

    private function isLineItemsGrouped(): bool
    {
        $parameters = $this->parameters->get('_parameters', []);

        return isset($parameters['group']) ? filter_var($parameters['group'], FILTER_VALIDATE_BOOLEAN) : false;
    }

    private function canBeGrouped(int $checkoutId): bool
    {
        if (isset($this->cache['canBeGrouped'][$checkoutId])) {
            return $this->cache['canBeGrouped'][$checkoutId];
        }

        $canBeGrouped = $this->doctrine->getRepository(CheckoutLineItem::class)->canBeGrouped($checkoutId);
        $this->cache['canBeGrouped'][$checkoutId] = $canBeGrouped;

        return $canBeGrouped;
    }

    private function getCheckout(int $checkoutId): ?Checkout
    {
        if (isset($this->cache['checkouts']) && \array_key_exists($checkoutId, $this->cache['checkouts'])) {
            return $this->cache['checkouts'][$checkoutId];
        }

        $checkout = $this->doctrine->getRepository(Checkout::class)->find($checkoutId);
        $this->cache['checkouts'][$checkoutId] = $checkout;

        return $checkout;
    }
}
