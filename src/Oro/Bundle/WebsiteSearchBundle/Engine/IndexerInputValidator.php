<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Provider\ReindexationWebsiteProviderInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Validates engine parameters, parses them and returns list of affected entities and websites.
 */
class IndexerInputValidator
{
    use ContextTrait;

    private WebsiteProviderInterface $websiteProvider;
    private SearchMappingProvider $mappingProvider;
    private ManagerRegistry $managerRegistry;
    private ReindexationWebsiteProviderInterface $reindexationWebsiteProvider;
    private TokenAccessorInterface $tokenAccessor;

    public function __construct(
        WebsiteProviderInterface $websiteProvider,
        SearchMappingProvider $mappingProvider,
        ManagerRegistry $managerRegistry,
        ReindexationWebsiteProviderInterface $provider,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->websiteProvider = $websiteProvider;
        $this->mappingProvider = $mappingProvider;
        $this->managerRegistry = $managerRegistry;
        $this->reindexationWebsiteProvider = $provider;
        $this->tokenAccessor = $tokenAccessor;
    }

    public function validateRequestParameters(array|string|null $classOrClasses, array $context): array
    {
        $parameters = $this->validateClassAndContext(['class' => $classOrClasses, 'context' => $context]);

        return [$parameters['class'], $this->getContextWebsiteIds($parameters['context'])];
    }

    public function validateClassAndContext(array $parameters): array
    {
        $resolver = $this->getOptionResolver();
        $this->configureClassOptions($resolver);
        $this->configureGranulizeOptions($resolver);
        $this->configureContextOptions($resolver);

        return $resolver->resolve($parameters);
    }

    public function configureContextOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setRequired('context');
        $optionsResolver->setAllowedTypes('context', 'array');
        $optionsResolver->setDefault('context', function (OptionsResolver $resolver) {
            $resolver->setDefined('skip_pre_processing');
            $resolver->setDefined(AbstractIndexer::CONTEXT_FIELD_GROUPS);
            $resolver->setDefined(AbstractIndexer::CONTEXT_ENTITY_CLASS_KEY);
            $resolver->setDefined(AbstractIndexer::CONTEXT_WEBSITE_IDS);
            $resolver->setDefined(AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY);
            $resolver->setDefined(AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY);

            $resolver->setAllowedTypes('skip_pre_processing', ['bool']);
            $resolver->setAllowedTypes(AbstractIndexer::CONTEXT_FIELD_GROUPS, ['string[]']);
            $resolver->setAllowedTypes(AbstractIndexer::CONTEXT_WEBSITE_IDS, ['int[]', 'string[]']);
            $resolver->setAllowedTypes(AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY, ['int[]', 'string[]']);
            $resolver->setAllowedTypes(AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY, 'int');

            $organization = $this->tokenAccessor->getOrganization();
            $resolver->setDefault(
                AbstractIndexer::CONTEXT_WEBSITE_IDS,
                $organization
                    ? $this->reindexationWebsiteProvider->getReindexationWebsiteIdsForOrganization($organization)
                    : $this->websiteProvider->getWebsiteIds()
            );
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

    public function configureClassOptions(OptionsResolver $optionsResolver): void
    {
        $classesNormalizer = static fn ($classes) => is_array($classes) ? $classes : array_filter([$classes]);
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

    public function configureEntityOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setRequired('entity');
        $optionsResolver->setAllowedTypes('entity', 'array');
        $optionsResolver->setAllowedValues('entity', function ($value) {
            if (!count($value)) {
                throw new InvalidOptionsException('Option "entity" was not expected to be empty');
            }

            return true;
        });

        $optionsResolver->setDefault('entity', function (OptionsResolver $resolver, Options $options) {
            $resolver->setPrototype(true);
            $resolver->setRequired('class');
            $resolver->setRequired('id');
            $resolver->setAllowedValues('class', fn ($class) => $this->mappingProvider->isClassSupported($class));
            $resolver->setAllowedTypes('id', 'int');
        });

        $optionsResolver->setNormalizer('entity', function (Options $options, array $value) {
            return array_map(
                fn (array $entityData) => $this->getReference($entityData['class'], $entityData['id']),
                $value
            );
        });
    }

    private function getReference(string $entityClass, int $entityId): object
    {
        return $this->managerRegistry->getManagerForClass($entityClass)->getReference($entityClass, $entityId);
    }

    public function configureGranulizeOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setRequired('granulize');
        $optionsResolver->setDefault('granulize', false);
        $optionsResolver->setAllowedTypes('granulize', ['bool']);
    }

    protected function getOptionResolver(): OptionsResolver
    {
        return new OptionsResolver();
    }
}
