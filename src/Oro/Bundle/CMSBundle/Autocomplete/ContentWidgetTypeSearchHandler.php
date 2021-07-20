<?php

namespace Oro\Bundle\CMSBundle\Autocomplete;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeInterface;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Content widget types autocomplete search handler
 */
class ContentWidgetTypeSearchHandler implements SearchHandlerInterface
{
    /** @var ContentWidgetTypeRegistry */
    private $registry;

    /** @var TranslatorInterface */
    private $translator;

    /** @var PropertyAccessor */
    private $propertyAccessor;

    public function __construct(
        ContentWidgetTypeRegistry $registry,
        TranslatorInterface $translator,
        PropertyAccessor $propertyAccessor
    ) {
        $this->registry = $registry;
        $this->translator = $translator;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function search($query, $page, $perPage, $searchById = false): array
    {
        $types = $this->registry->getTypes();

        if ($searchById) {
            $names = array_filter(explode(',', $query));
            $types = array_filter(
                $types,
                static function (ContentWidgetTypeInterface $type) use ($names) {
                    return \in_array($type::getName(), $names, true);
                }
            );
        } elseif ($query) {
            $query = trim($query);

            $types = array_filter(
                $types,
                static function (ContentWidgetTypeInterface $type) use ($query) {
                    return stripos($type::getName(), $query) !== false;
                }
            );
        }

        return [
            'results' => array_map(
                function (ContentWidgetTypeInterface $attributeFamily) {
                    return $this->convertItem($attributeFamily);
                },
                array_slice($types, $page - 1, $perPage)
            ),
            'more' => false
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        return ['label'];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityName(): string
    {
        return ContentWidgetTypeInterface::class;
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item): array
    {
        $result = ['id' => $this->propertyAccessor->getValue($item, 'name')];

        foreach ($this->getProperties() as $property) {
            $result[$property] = $this->translator->trans((string) $this->propertyAccessor->getValue($item, $property));
        }

        return $result;
    }
}
