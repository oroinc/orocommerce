<?php

namespace Oro\Bundle\PricingBundle\Async\Topic;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\MessageQueueBundle\Compatibility\TopicInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Updates combined price lists in case of changes in structure of original price lists.
 */
class RebuildCombinedPriceListsTopic implements TopicInterface
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

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        /*
         * Set `force` to true to rebuild combined price lists for entities with default and self fallback.
         * Passing `force` without restrictions by other entities means rebuilding all combined price lists.
         */
        $resolver->setDefault('force', false);
        $resolver->setAllowedTypes('force', 'bool');

        $this->configureMessageBodyIncludeWebsite($resolver);
        $this->configureMessageBodyIncludeCustomerGroup($resolver);
        $this->configureMessageBodyIncludeCustomer($resolver);
        $this->configureMessageBodyIncludeAssignments($resolver);
    }

    private function configureMessageBodyIncludeWebsite(OptionsResolver $resolver): void
    {
        // Website ID for which combined price lists should be rebuilt
        $resolver->setDefault('website', null);
        $resolver->setAllowedTypes('website', ['null', 'int', 'string']);
        $resolver->setNormalizer(
            'website',
            function (Options $options, $value): ?Website {
                if (!$value) {
                    return null;
                }

                $entity = $this->registry->getRepository(Website::class)->find($value);
                if (null === $entity) {
                    throw new InvalidOptionsException('Website was not found.');
                }

                return $entity;
            }
        );
    }

    private function configureMessageBodyIncludeCustomerGroup(OptionsResolver $resolver): void
    {
        // Customer Group ID for which combined price lists should be rebuilt. Requires Website.
        $resolver->setDefault('customerGroup', null);
        $resolver->setAllowedTypes('customerGroup', ['null', 'int', 'string']);
        $resolver->setNormalizer(
            'customerGroup',
            function (Options $options, $value): ?CustomerGroup {
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
            }
        );
    }

    private function configureMessageBodyIncludeCustomer(OptionsResolver $resolver): void
    {
        // Customer ID for whom combined price list should be rebuilt. Requires Website.
        $resolver->setDefault('customer', null);
        $resolver->setAllowedTypes('customer', ['null', 'int', 'string']);
        $resolver->setNormalizer(
            'customer',
            function (Options $options, $value): ?Customer {
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
            }
        );
    }

    public function configureMessageBodyIncludeAssignments(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined('assignments')
            ->setDefault('assignments', [])
            ->setAllowedTypes('assignments', ['array'])
            ->setAllowedValues('assignments', function (&$elements) {
                $assigmentResolver = $this->getAssignmentResolver();
                $elements = array_map([$assigmentResolver, 'resolve'], $elements);

                return true;
            })
            ->setNormalizer('assignments', function (Options $options, array $value) {
                if (!$value) {
                    $assigment = [
                        'force' => $options['force'],
                        'website' => $options['website'],
                        'customer' => $options['customer'],
                        'customerGroup' => $options['customerGroup'],
                    ];

                    $value[] = $assigment;
                }

                return $value;
            });
    }

    private function getAssignmentResolver(): OptionsResolver
    {
        $assignmentItemResolver = new OptionsResolver();
        $assignmentItemResolver
            ->setDefined('force')
            ->setDefault('force', false)
            ->setAllowedTypes('force', 'bool');

        $assignmentItemResolver
            ->setDefined('website')
            ->setDefault('website', null)
            ->setAllowedTypes('website', ['null', 'int'])
            ->setRequired('website');

        $assignmentItemResolver
            ->setDefined('customer')
            ->setDefault('customer', null)
            ->setAllowedTypes('customer', ['null', 'int']);

        $assignmentItemResolver
            ->setDefined('customerGroup')
            ->setDefault('customerGroup', null)
            ->setAllowedTypes('customerGroup', ['null', 'int']);

        return $assignmentItemResolver;
    }
}
