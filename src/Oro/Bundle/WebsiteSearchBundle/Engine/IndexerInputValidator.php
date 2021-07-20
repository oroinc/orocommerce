<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Validates engine parameters, parses them and returns list of affected entities and websites.
 */
class IndexerInputValidator
{
    use ContextTrait;

    /** @var WebsiteProviderInterface */
    protected $websiteProvider;

    /** @var SearchMappingProvider */
    protected $mappingProvider;

    public function __construct(WebsiteProviderInterface $websiteProvider, SearchMappingProvider $mappingProvider)
    {
        $this->websiteProvider = $websiteProvider;
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * @param string|string[] $classOrClasses
     * @param array $context
     *
     * @return array
     */
    public function validateRequestParameters($classOrClasses, array $context): array
    {
        $parameters = $this->validateClassAndContext(['class' => $classOrClasses, 'context' => $context]);

        return [$parameters['class'], $parameters['context'][AbstractIndexer::CONTEXT_WEBSITE_IDS]];
    }

    public function validateClassAndContext(array $parameters): array
    {
        $resolver = $this->getOptionResolver();
        $this->configureClassOptions($resolver);
        $this->configureGranulizeOptions($resolver);
        $this->configureContextOptions($resolver);

        return $resolver->resolve($parameters);
    }

    public function validateEntityAndContext(array $parameters): array
    {
        $resolver = $this->getOptionResolver();
        $this->configureEntityOptions($resolver);
        $this->configureContextOptions($resolver);

        return $resolver->resolve($parameters);
    }

    public function configureContextOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setRequired('context');
        $optionsResolver->setAllowedTypes('context', 'array');
        $optionsResolver->setDefault('context', function (OptionsResolver $resolver) {
            $resolver->setDefined('skip_pre_processing');
            $resolver->setDefined(AbstractIndexer::CONTEXT_WEBSITE_IDS);
            $resolver->setDefined(AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY);
            $resolver->setDefined(AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY);

            $resolver->setAllowedTypes('skip_pre_processing', ['bool']);
            $resolver->setAllowedTypes(AbstractIndexer::CONTEXT_WEBSITE_IDS, ['int[]', 'string[]']);
            $resolver->setAllowedTypes(AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY, ['int[]', 'string[]']);
            $resolver->setAllowedTypes(AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY, 'int');

            $resolver->setDefault(AbstractIndexer::CONTEXT_WEBSITE_IDS, $this->websiteProvider->getWebsiteIds());
            $resolver->setNormalizer(
                AbstractIndexer::CONTEXT_WEBSITE_IDS,
                fn (OptionsResolver $resolver, array $ids) => $ids ?: $this->websiteProvider->getWebsiteIds()
            );

            /**
             * The data included in the index comes from different sources, so it is impossible to guarantee that
             * their type will be the same, so allow the types 'int' and 'string' with subsequent normalization
             * to the 'int' type.
             */
            $toIntCallBack = fn (OptionsResolver $resolver, array $ids) => array_map('intval', $ids);
            $resolver->addNormalizer(AbstractIndexer::CONTEXT_WEBSITE_IDS, $toIntCallBack);
            $resolver->addNormalizer(AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY, $toIntCallBack);
        });
    }

    private function configureClassOptions(OptionsResolver $optionsResolver)
    {
        $classesNormalizer = fn ($classes) => is_array($classes) ? $classes : array_filter([$classes]);
        $optionsResolver->setDefined('class');
        $optionsResolver->setDefault('class', []);
        $optionsResolver->setAllowedValues('class', function ($classes) use ($classesNormalizer) {
            $classes = $classesNormalizer($classes);
            $supported = array_filter($classes, fn ($class) => $this->mappingProvider->isClassSupported($class));

            return $classes === $supported;
        });

        $optionsResolver->setNormalizer('class', function (Options $options, $classes) use ($classesNormalizer) {
            $definedClass = $this->mappingProvider->getEntityClasses();

            return $classesNormalizer($classes) ?: $definedClass;
        });
    }

    private function configureEntityOptions(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setRequired('entity');
        $optionsResolver->setDefault('entity', function (OptionsResolver $resolver, Options $options) {
            $resolver->setRequired('class');
            $resolver->setRequired('id');
            $resolver->setAllowedValues('class', fn ($class) => $this->mappingProvider->isClassSupported($class));
            $resolver->setAllowedTypes('id', ['int']);
        });
    }

    private function configureGranulizeOptions(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setRequired('granulize');
        $optionsResolver->setDefault('granulize', false);
        $optionsResolver->setNormalizer('granulize', function (Options $options, $granulize): bool {
            return !empty($granulize);
        });
    }

    protected function getOptionResolver(): OptionsResolver
    {
        return new OptionsResolver();
    }
}
