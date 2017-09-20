<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Normalizer;

use Symfony\Component\Config\FileLocator;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductImageNormalizer extends ConfigurableEntityNormalizer
{
    /**
     * @var string
     */
    protected $productImageClass;

    /**
     * @var  ImageTypeProvider
     */
    protected $imageTypeProvider;

    /**
     * @var  FileLocator
     */
    protected $fileLocator;

    /**
     * @var  string $rootDir
     */
    protected $rootDir;

    public function setImageTypeProvider(ImageTypeProvider $imageTypeProvider)
    {
        $this->imageTypeProvider = $imageTypeProvider;
    }

    /**
     * @param $productImageClass
     */
    public function setProductImageClass($productImageClass)
    {
        $this->productImageClass = $productImageClass;
    }

    /**
     * @param FileLocator
     */
    public function setFileLocator($fileLocator)
    {
        $this->fileLocator = $fileLocator;
    }

    /**
     * @param $rootDir
     */
    public function setRootDir($rootDir)
    {
        $this->rootDir = $rootDir;
    }


    /**
     * @param Product $object
     *
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $data = parent::normalize($object, $format, $context);

        $imageTypesKeys = array_keys($this->imageTypeProvider->getImageTypes());
        $availableTypesArray = array_fill_keys($imageTypesKeys, false);

        foreach ($data['types'] as $key => $type) {
            if (array_key_exists($type['type'], $availableTypesArray)) {
                $availableTypesArray[$type['type']] = true;
            }
        }

        $data['types'] = $availableTypesArray;
        $data['image']['name'] = $object->getImage()->getOriginalFileName();

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        try {
            $imagePath = $this->fileLocator->locate(sprintf('%s/import_export/product_images/%s', $this->rootDir, $data['image']['name']));
        } catch (\Exception $e) {
            $imagePath = null;
        }

        if (is_array($imagePath)) {
            $imagePath = current($imagePath);
        }

        $data['image'] = $imagePath;
        $imageTypes = $this->imageTypeProvider->getImageTypes();

        foreach ($data['types'] as $type => $value) {
            if (!array_key_exists($type, $imageTypes) || !boolval($value)) {
                unset($imageTypes[$type]);
            }
        }

        $data['types'] = array_keys($imageTypes);

        return parent::denormalize($data, $class, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return is_a($data, $this->productImageClass);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return is_a($type, $this->productImageClass, true);
    }
}
