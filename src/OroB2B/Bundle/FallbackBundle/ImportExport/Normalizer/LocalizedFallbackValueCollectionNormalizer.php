<?php

namespace OroB2B\Bundle\FallbackBundle\ImportExport\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\CollectionNormalizer;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;

class LocalizedFallbackValueCollectionNormalizer extends CollectionNormalizer
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $localizedFallbackValueClass;

    /**
     * @param ManagerRegistry $registry
     * @param string $localizedFallbackValueClass
     */
    public function __construct(ManagerRegistry $registry, $localizedFallbackValueClass)
    {
        $this->registry = $registry;
        $this->localizedFallbackValueClass = $localizedFallbackValueClass;
    }

    /** {@inheritdoc} */
    public function normalize($object, $format = null, array $context = [])
    {
        $result = [];

        /** @var LocalizedFallbackValue $item */
        foreach ($object as $item) {
            $serializedItem = $this->serializer->normalize($item, $format, $context);
            $result[LocaleCodeFormatter::format($item->getLocale())] = $serializedItem;
        }

        return $result;
    }

    /** {@inheritdoc} */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!is_array($data)) {
            return new ArrayCollection();
        }
        $itemType = $this->getItemType($class);
        if (!$itemType) {
            return new ArrayCollection($data);
        }
        $result = new ArrayCollection();
        foreach ($data as $item) {
            /** @var LocalizedFallbackValue $object */
            $object = $this->serializer->denormalize($item, $itemType, $format, $context);

            $result->set(LocaleCodeFormatter::format($object->getLocale()), $object);
        }

        return $result;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        if (!parent::supportsNormalization($data, $format, $context)) {
            return false;
        }

        return $this->isApplicable($context);
    }

    /** {@inheritdoc} */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        if (!parent::supportsDenormalization($data, $type, $format, $context)) {
            return false;
        }

        return $this->isApplicable($context);
    }

    /**
     * @param array $context
     * @return bool
     */
    protected function isApplicable(array $context = [])
    {
        if (!isset($context['entityName'], $context['fieldName'])) {
            return false;
        }

        $className = $context['entityName'];
        $fieldName = $context['fieldName'];
        $metadata = $this->registry->getManagerForClass($className)->getClassMetadata($className);
        if (!$metadata->hasAssociation($fieldName)) {
            return false;
        }

        $targetClass = $metadata->getAssociationTargetClass($fieldName);

        return is_a($targetClass, $this->localizedFallbackValueClass, true);
    }
}
