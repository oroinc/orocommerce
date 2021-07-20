<?php

namespace Oro\Bundle\PromotionBundle\Twig;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PromotionBundle\Layout\DataProvider\DiscountsInformationDataProvider;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig functios to retrieve line item discounts information:
 *   - line_items_discounts
 */
class DiscountsInformationExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [new TwigFunction('line_items_discounts', [$this, 'getLineItemsDiscounts'])];
    }

    /**
     * @param object $sourceEntity
     *
     * @return array
     */
    public function getLineItemsDiscounts($sourceEntity)
    {
        $lineItemsDiscounts = $this->container->get('oro_promotion.layout.discount_information_data_provider')
            ->getDiscountLineItemDiscounts($sourceEntity);

        $discounts = [];
        foreach ($sourceEntity->getLineItems() as $lineItem) {
            $discounts[$lineItem->getId()] = null;
            if ($lineItemsDiscounts->contains($lineItem)) {
                $discount = $lineItemsDiscounts->get($lineItem);
                /** @var Price $discountPrice */
                $discountPrice = $discount['total'];
                $discounts[$lineItem->getId()] = [
                    'value' => $discountPrice->getValue(),
                    'currency' => $discountPrice->getCurrency(),
                ];
            }
        }

        return $discounts;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_promotion.layout.discount_information_data_provider' => DiscountsInformationDataProvider::class,
        ];
    }
}
