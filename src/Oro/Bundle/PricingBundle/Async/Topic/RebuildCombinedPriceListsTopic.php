<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Updates combined price lists in case of changes in structure of original price lists.
 */
class RebuildCombinedPriceListsTopic extends AbstractTopic
{
    public const NAME = 'oro_pricing.price_lists.cpl.rebuild';

    private ManagerRegistry $registry;

    public function __construct(
        ManagerRegistry $registry
    ) {
        $this->registry = $registry;
    }

    public static function getName(): string
    {
        return static::NAME;
    }

    public static function getDescription(): string
    {
        return 'Updates combined price lists in case of changes in structure of original price lists.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver->define('force')
            ->info(
                'Set `force` to true to rebuild combined price lists for entities with default and self fallback. ' .
                'Passing `force` without restrictions by other entities means rebuilding all combined price lists.'
            )
            ->default(false)
            ->allowedTypes('bool');

        $this->configureMessageBodyIncludeWebsite($resolver);
        $this->configureMessageBodyIncludeCustomerGroup($resolver);
        $this->configureMessageBodyIncludeCustomer($resolver);
    }

    private function configureMessageBodyIncludeWebsite(OptionsResolver $resolver): void
    {
        $resolver->define('website')
            ->info('Website ID for which combined price lists should be rebuilt')
            ->default(null)
            ->allowedTypes('null', 'int', 'string')
            ->normalize(function (Options $options, $value): ?Website {
                if (!$value) {
                    return null;
                }

                $entity = $this->registry->getRepository(Website::class)->find($value);
                if (null === $entity) {
                    throw new InvalidOptionsException('Website was not found.');
                }

                return $entity;
            });
    }

    private function configureMessageBodyIncludeCustomerGroup(OptionsResolver $resolver): void
    {
        $resolver->define('customerGroup')
            ->info('Customer Group ID for which combined price lists should be rebuilt. Requires Website.')
            ->default(null)
            ->allowedTypes('null', 'int', 'string')
            ->normalize(function (Options $options, $value): ?CustomerGroup {
                if (!$value) {
                    return null;
                }

                if (empty($options['website'])) {
                    throw new MissingOptionsException('The "website" option is required when "customerGroup" is set.');
                }

                $entity = $this->registry->getRepository(CustomerGroup::class)->find($value);
                if (null === $entity) {
                    throw new InvalidOptionsException('Customer Group was not found.');
                }

                return $entity;
            });
    }

    private function configureMessageBodyIncludeCustomer(OptionsResolver $resolver): void
    {
        $resolver->define('customer')
            ->info('Customer ID for whom combined price list should be rebuilt. Requires Website.')
            ->default(null)
            ->allowedTypes('null', 'int', 'string')
            ->normalize(function (Options $options, $value): ?Customer {
                if (!$value) {
                    return null;
                }

                if (empty($options['website'])) {
                    throw new MissingOptionsException('The "website" option is required when "customer" is set.');
                }

                $entity = $this->registry->getRepository(Customer::class)->find($value);
                if (null === $entity) {
                    throw new InvalidOptionsException('Customer was not found.');
                }

                return $entity;
            });
    }
}
