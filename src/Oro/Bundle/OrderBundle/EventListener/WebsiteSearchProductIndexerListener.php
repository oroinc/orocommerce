<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\CustomerBundle\Placeholder\CustomerUserIdPlaceholder;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\OrderBundle\Provider\LatestOrderedProductsInfoProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;

/**
 * Adds information about users who ordered products to website search index for product entity
 */
class WebsiteSearchProductIndexerListener
{
    use FeatureCheckerHolderTrait;
    use ContextTrait;

    /**
     * @var WebsiteContextManager
     */
    private $websiteContextManager;

    /**
     * @var LatestOrderedProductsInfoProvider
     */
    protected $latestOrderedProductsInfoProvider;

    public function __construct(
        WebsiteContextManager $websiteContextManager,
        LatestOrderedProductsInfoProvider $latestOrderedProductsInfoProvider
    ) {
        $this->websiteContextManager = $websiteContextManager;
        $this->latestOrderedProductsInfoProvider = $latestOrderedProductsInfoProvider;
    }

    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        if (!$this->hasContextFieldGroup($event->getContext(), 'order')) {
            return;
        }

        $website = $this->websiteContextManager->getWebsite($event->getContext());
        if (!$website) {
            $event->stopPropagation();

            return;
        }

        if (!$this->isFeaturesEnabled($website)) {
            return;
        }

        /** @var Product[] $products */
        $products = $event->getEntities();

        $productIds = array_map(
            static function (Product $product) {
                return $product->getId();
            },
            $products
        );

        $latestOrderedProductsInfo = $this->latestOrderedProductsInfoProvider
            ->getLatestOrderedProductsInfo($productIds, $website->getId());

        foreach ($products as $product) {
            if (isset($latestOrderedProductsInfo[$product->getId()])) {
                $orderInfoArray = $latestOrderedProductsInfo[$product->getId()];
                foreach ($orderInfoArray as $orderInfo) {
                    $placeholders = [CustomerUserIdPlaceholder::NAME => $orderInfo['customer_user_id']];
                    $event->addPlaceholderField(
                        $product->getId(),
                        'ordered_at_by.CUSTOMER_USER_ID',
                        $orderInfo['created_at'],
                        $placeholders
                    );
                }
            }
        }
    }
}
