<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Normalizer;

use Oro\Bundle\AttachmentBundle\ImportExport\FileNormalizer;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;
use Oro\Bundle\ProductBundle\Entity\ProductImage;

/**
 * Adds corresponding changes to the handling of files during product image import.
 */
class ProductImageAwareFileNormalizerDecorator implements DenormalizerInterface, NormalizerInterface
{
    /** @var FileNormalizer */
    private $fileNormalizer;

    /**
     * @param FileNormalizer $fileNormalizer
     */
    public function __construct(FileNormalizer $fileNormalizer)
    {
        $this->fileNormalizer = $fileNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $class, $format = null, array $context = []): bool
    {
        return $this->fileNormalizer->supportsDenormalization(...func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $this->fileNormalizer->supportsNormalization(...func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (isset($context['entityName']) && $context['entityName'] === ProductImage::class) {
            $data = ['uri' => $data, 'uuid' => ''];
        }

        return $this->fileNormalizer->denormalize($data, $class, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $result = $this->fileNormalizer->normalize(...func_get_args());

        if (isset($context['entityName']) && $context['entityName'] === ProductImage::class) {
            $result = $result['uri'];
        }

        return $result;
    }
}
