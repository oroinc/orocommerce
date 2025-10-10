<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Symfony\Component\Config\FileLocator;

/**
 * Import/export normalizer for ProductImage entities.
 */
class ProductImageNormalizer extends ConfigurableEntityNormalizer
{
    /**
     * @var string $productImageClass
     */
    protected $productImageClass;

    /**
     * @var  ImageTypeProvider $imageTypeProvider
     */
    protected $imageTypeProvider;

    /**
     * @var  FileLocator $fileLocator
     */
    protected $fileLocator;

    public function setImageTypeProvider(ImageTypeProvider $imageTypeProvider)
    {
        $this->imageTypeProvider = $imageTypeProvider;
    }

    /**
     * @param string $productImageClass
     */
    public function setProductImageClass($productImageClass)
    {
        $this->productImageClass = $productImageClass;
    }

    public function setFileLocator(FileLocator $fileLocator)
    {
        $this->fileLocator = $fileLocator;
    }

    /**
     * @param ProductImage $productImage
     *
     */
    #[\Override]
    public function normalize(
        mixed $productImage,
        ?string $format = null,
        array $context = []
    ): float|int|bool|\ArrayObject|array|string|null {
        $data = parent::normalize($productImage, $format, $context);

        $name = $productImage->getImage()->getOriginalFileName();
        if (!$name) {
            $name = $productImage->getImage()->getFilename();
        }

        if (array_key_exists('image', $data)) {
            $data['image']['name'] = $name;
        }

        if (!array_key_exists('types', $data)) {
            return $data;
        }

        $imageTypesKeys = array_keys($this->imageTypeProvider->getImageTypes());
        $availableTypesArray = array_fill_keys($imageTypesKeys, false);

        foreach ($data['types'] as $key => $type) {
            if (array_key_exists($type['type'], $availableTypesArray)) {
                $availableTypesArray[$type['type']] = true;
            }
        }

        $data['types'] = $availableTypesArray;
        $data['image']['name'] = $name;

        return $data;
    }

    #[\Override]
    public function denormalize(
        mixed $productImageData,
        string $type,
        ?string $format = null,
        array $context = []
    ): mixed {
        $imageTypes = $this->imageTypeProvider->getImageTypes();
        foreach ($productImageData['types'] as $imageType => $value) {
            if (!array_key_exists($imageType, $imageTypes) || !boolval($value)) {
                unset($imageTypes[$imageType]);
            }
        }

        $imagePath = '';
        if (!empty($productImageData['image']['name'])) {
            $imagePath = $productImageData['image']['name'];
        }

        $productImageData['image'] = $imagePath;
        $productImageData['types'] = array_keys($imageTypes);

        return parent::denormalize($productImageData, $type, $format, $context);
    }

    #[\Override]
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_a($data, $this->productImageClass);
    }

    #[\Override]
    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        return is_array($data) && is_a($type, $this->productImageClass, true);
    }
}
