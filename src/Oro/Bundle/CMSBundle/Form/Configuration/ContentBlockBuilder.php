<?php

namespace Oro\Bundle\CMSBundle\Form\Configuration;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Form\Type\ContentBlockSelectType;
use Oro\Bundle\ThemeBundle\Form\Configuration\AbstractChoiceBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Represents builder for content_block_selector option
 */
class ContentBlockBuilder extends AbstractChoiceBuilder
{
    public function __construct(
        Packages $packages,
        private DataTransformerInterface $dataTransformer,
        private ManagerRegistry $registry,
        private LoggerInterface $logger
    ) {
        parent::__construct($packages);
    }

    #[\Override]
    public static function getType(): string
    {
        return 'content_block_selector';
    }

    #[\Override]
    protected function getTypeClass(): string
    {
        return ContentBlockSelectType::class;
    }

    #[\Override]
    protected function getDefaultOptions(): array
    {
        return [];
    }

    #[\Override]
    public function buildOption(FormBuilderInterface $builder, array $option): void
    {
        parent::buildOption($builder, $option);

        $builder->addModelTransformer(new CallbackTransformer(
            function ($data) use ($option) {
                if (!isset($data[$option['name']]) || is_object($data[$option['name']])) {
                    return $data;
                }

                $object = $this->dataTransformer->reverseTransform($data[$option['name']]);

                $data[$option['name']] = $object;

                return $data;
            },
            function ($data) use ($option) {
                if (isset($data[$option['name']]) && is_object($data[$option['name']])) {
                    $data[$option['name']] = $this->dataTransformer->transform($data[$option['name']]);
                }
                return $data;
            }
        ));
    }

    #[\Override]
    protected function getConfiguredOptions($option): array
    {
        $options = parent::getConfiguredOptions($option);

        foreach ($options['choices'] ?? [] as $key => $alias) {
            $block = $this->getRepository(ContentBlock::class)?->findOneBy(['alias' => $alias]);
            if (!$block) {
                $this->logger->warning(
                    \sprintf('The content block with "%s" alias was not found for "%s".', $alias, self::getType())
                );
                unset($options['choices'][$key]);

                continue;
            }

            $options['choices'][$key] = $block;
        }

        return $options;
    }

    #[\Override]
    protected function getOptionPreview(array $option, mixed $value = null, bool $default = false): ?string
    {
        if ($value && !$value instanceof ContentBlock && $value !== self::DEFAULT_PREVIEW_KEY) {
            $value = $this->getRepository(ContentBlock::class)->find($value);
        }

        $value = $value instanceof ContentBlock ? $value->getAlias() : $value;

        return parent::getOptionPreview($option, $value, $default);
    }

    private function getRepository(string $class): ?ObjectRepository
    {
        return $this->registry->getRepository($class);
    }
}
