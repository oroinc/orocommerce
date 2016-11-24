<?php

namespace Oro\Bundle\WebCatalogBundle\ContentVariantType;

use Oro\Bundle\WebCatalogBundle\Exception\InvalidArgumentException;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

class ContentVariantTypeRegistry
{
    /**
     * @var ContentVariantTypeInterface[]
     */
    private $contentVariantTypes = [];

    /**
     * @param ContentVariantTypeInterface $contentVariantType
     */
    public function addContentVariantType(ContentVariantTypeInterface $contentVariantType)
    {
        $this->contentVariantTypes[$contentVariantType->getName()] = $contentVariantType;
    }

    /**
     * @param string $contentVariantTypeName
     * @return ContentVariantTypeInterface
     */
    public function getContentVariantType($contentVariantTypeName)
    {
        if (!array_key_exists($contentVariantTypeName, $this->contentVariantTypes)) {
            throw new InvalidArgumentException(
                sprintf('Content variant type "%s" is not known.', $contentVariantTypeName)
            );
        }

        return $this->contentVariantTypes[$contentVariantTypeName];
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
     * @return ContentVariantTypeInterface[]
     */
    public function getContentVariantTypes()
    {
        return $this->contentVariantTypes;
    }

    /**
     * @return ContentVariantTypeInterface[]
     */
    public function getAllowedContentVariantTypes()
    {
        $types = [];
        foreach ($this->contentVariantTypes as $name => $type) {
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
}
