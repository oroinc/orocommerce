<?php

namespace Oro\Bundle\ProductBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Accumulates information about products from the segment on re-index in the intermediate storage,
 * which will be processed by the dependent job.
 */
class ReindexProductCollectionBySegmentTopic extends AbstractTopic
{
    public const NAME = 'oro_product.reindex_product_collection_by_segment';

    public const OPTION_NAME_ID = 'id';
    public const OPTION_NAME_JOB_ID = 'job_id';
    public const OPTION_NAME_WEBSITE_IDS = 'website_ids';
    public const OPTION_NAME_DEFINITION = 'definition';
    public const OPTION_NAME_IS_FULL = 'is_full';
    public const OPTION_NAME_ADDITIONAL_PRODUCTS = 'additional_products';

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getDescription(): string
    {
        return 'Accumulates information about products from the segment on re-index in the intermediate storage' .
        ', which will be processed by the dependent job.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            self::OPTION_NAME_JOB_ID,
            self::OPTION_NAME_WEBSITE_IDS,
            self::OPTION_NAME_IS_FULL,
        ]);

        $resolver->setDefined([
            self::OPTION_NAME_ID,
            self::OPTION_NAME_DEFINITION,
            self::OPTION_NAME_ADDITIONAL_PRODUCTS,
        ]);

        $resolver->setAllowedTypes(self::OPTION_NAME_JOB_ID, ['int']);
        $resolver->setAllowedTypes(self::OPTION_NAME_WEBSITE_IDS, ['string[]', 'int[]']);
        $resolver->setAllowedTypes(self::OPTION_NAME_ID, ['int', 'null']);
        $resolver->setAllowedTypes(self::OPTION_NAME_DEFINITION, ['string', 'null']);
        $resolver->setAllowedTypes(self::OPTION_NAME_IS_FULL, ['boolean']);
        $resolver->setAllowedTypes(self::OPTION_NAME_ADDITIONAL_PRODUCTS, ['string[]', 'int[]']);
        $resolver->setDefault(self::OPTION_NAME_ADDITIONAL_PRODUCTS, []);
        $resolver->setDefault(self::OPTION_NAME_ID, null);
        $resolver->setDefault(self::OPTION_NAME_DEFINITION, null);

        /**
         * Validate depends options "id" and "definition"
         */
        $resolver->setNormalizer(self::OPTION_NAME_JOB_ID, static function (Options $options, $value) {
            $isIdOptionSet = $options->offsetExists(self::OPTION_NAME_ID);
            $isDefinitionOptionSet = $options->offsetExists(self::OPTION_NAME_DEFINITION);
            $isOptionIdInvalid = !$isIdOptionSet || null === $options->offsetGet(self::OPTION_NAME_ID);
            $isOptionDefinitionInvalid = !$isDefinitionOptionSet || null === $options->offsetGet(
                self::OPTION_NAME_DEFINITION
            );

            if ($isOptionIdInvalid && $isOptionDefinitionInvalid) {
                throw new InvalidOptionsException(
                    'One of these options "id" or "definition" must be present and has not null value.'
                );
            }

            return $value;
        });
    }
}
