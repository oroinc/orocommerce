<?php

namespace Oro\Bundle\WebCatalogBundle\ContentVariantType;

use Oro\Bundle\WebCatalogBundle\Exception\InvalidArgumentException;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The registry of content variant types.
 */
class ContentVariantTypeRegistry implements ResetInterface
{
    /** @var iterable|ContentVariantTypeInterface[] */
    private $contentVariantTypes;

    /** @var ContentVariantTypeInterface[]|null [name => content variant type, ...] */
    private $initializedContentVariantTypes;

    /**
     * @param iterable|ContentVariantTypeInterface[] $contentVariantTypes
     */
    public function __construct($contentVariantTypes)
    {
        $this->contentVariantTypes = $contentVariantTypes;
    }

    /**
     * @param string $contentVariantTypeName
     * @return ContentVariantTypeInterface
     */
    public function getContentVariantType($contentVariantTypeName)
    {
        $contentVariantTypes = $this->getContentVariantTypes();
        if (!isset($contentVariantTypes[$contentVariantTypeName])) {
            throw new InvalidArgumentException(
                sprintf('Content variant type "%s" is not known.', $contentVariantTypeName)
            );
        }

        return $contentVariantTypes[$contentVariantTypeName];
    }

    /**
     * @param ContentVariantInterface $contentVariant
     * @return ContentVariantTypeInterface
     */
    public function getContentVariantTypeByContentVariant(ContentVariantInterface $contentVariant)
    {
        return $this->getContentVariantType($contentVariant->getType());
    }

    /**
     * @return ContentVariantTypeInterface[] [name => content variant type, ...]
     */
    public function getContentVariantTypes()
    {
        if (null === $this->initializedContentVariantTypes) {
            $this->initializedContentVariantTypes = [];
            foreach ($this->contentVariantTypes as $type) {
                $this->initializedContentVariantTypes[$type->getName()] = $type;
            }
        }

        return $this->initializedContentVariantTypes;
    }

    /**
     * @return ContentVariantTypeInterface[]
     */
    public function getAllowedContentVariantTypes()
    {
        $types = [];
        $contentVariantTypes = $this->getContentVariantTypes();
        foreach ($contentVariantTypes as $name => $type) {
            if ($type->isAllowed()) {
                $types[$name] = $type;
            }
        }

        return $types;
    }

    /**
     * @param string $type
     * @return string
     */
    public function getFormTypeByType($type)
    {
        foreach ($this->getContentVariantTypes() as $contentVariantType) {
            if ($contentVariantType->getName() === $type) {
                return $contentVariantType->getFormType();
            }
        }

        throw new InvalidArgumentException(
            sprintf('Content variant type "%s" is not known.', $type)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function reset()
    {
        $this->initializedContentVariantTypes = null;
    }
}
