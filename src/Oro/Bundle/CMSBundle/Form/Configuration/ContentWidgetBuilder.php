<?php

namespace Oro\Bundle\CMSBundle\Form\Configuration;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Form\Type\ContentWidgetSelectType;
use Oro\Bundle\ThemeBundle\Form\Configuration\AbstractChoiceBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Represents builder for content_widget_selector option
 */
class ContentWidgetBuilder extends AbstractChoiceBuilder
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
        return 'content_widget_selector';
    }

    #[\Override]
    protected function getTypeClass(): string
    {
        return ContentWidgetSelectType::class;
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

        foreach ($options['choices'] ?? [] as $key => $name) {
            $widget = $this->getRepository(ContentWidget::class)?->findOneBy(['name' => $name]);
            if (!$widget) {
                $this->logger->warning(
                    \sprintf('The content widget with "%s" name was not found for "%s".', $name, self::getType())
                );
                unset($options['choices'][$key]);

                continue;
            }

            $options['choices'][$key] = $widget;
        }

        return $options;
    }

    #[\Override]
    protected function getOptionPreview(array $option, mixed $value = null, bool $default = false): ?string
    {
        if ($value && !$value instanceof ContentWidget && $value !== self::DEFAULT_PREVIEW_KEY) {
            $value = $this->getRepository(ContentWidget::class)?->find($value);
        }

        $value = $value instanceof ContentWidget ? $value->getName() : $value;

        return parent::getOptionPreview($option, $value, $default);
    }

    private function getRepository(string $class): ?ObjectRepository
    {
        return $this->registry->getRepository($class);
    }
}
