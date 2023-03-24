<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Resolves flat prices.
 */
class ResolveFlatPriceTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public const NAME = 'oro_pricing.flat_price.resolve';

    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public static function getName(): string
    {
        return static::NAME;
    }

    public static function getDescription(): string
    {
        return 'Resolves flat product prices.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $this->configureMessageBodyIncludeProduct($resolver);
    }

    private function configureMessageBodyIncludeProduct(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined(['priceList', 'products'])
            ->setAllowedTypes('priceList', 'int')
            ->setAllowedTypes('products', ['int[]'])
            ->setDefault('products', [])
            ->setNormalizer('products', function (Options $options, $products) {
                $priceListId = $options['priceList'];
                if (!$products && $priceListId) {
                    /** @var  PriceListToProductRepository $priceListToProductRepository */
                    $priceListToProductRepository = $this->doctrine->getRepository(PriceListToProduct::class);

                    return $priceListToProductRepository->getProductIdsByPriceList($priceListId);
                }

                return $products;
            });
    }

    public function createJobName($messageBody): string
    {
        return sprintf('%s_%s', self::getName(), UUIDGenerator::v4());
    }
}
