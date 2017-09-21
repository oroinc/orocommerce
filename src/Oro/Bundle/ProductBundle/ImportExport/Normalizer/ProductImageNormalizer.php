<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Normalizer;

use Symfony\Component\Config\FileLocator;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\Product;

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

    /**
     * @var  string $rootDir
     */
    protected $rootDir;

    /**
     * @param ImageTypeProvider $imageTypeProvider
     */
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

    /**
     * @param FileLocator $fileLocator
     */
    public function setFileLocator($fileLocator)
    {
        $this->fileLocator = $fileLocator;
    }

    /**
     * @param string $rootDir
     */
    public function setRootDir($rootDir)
    {
        $this->rootDir = $rootDir;
    }
    
    /**
     * @param Product $product
     *
     * {@inheritdoc}
     */
    public function normalize($product, $format = null, array $context = [])
    {
        $data = parent::normalize($product, $format, $context);

        $imageTypesKeys = array_keys($this->imageTypeProvider->getImageTypes());
        $availableTypesArray = array_fill_keys($imageTypesKeys, false);

        foreach ($data['types'] as $key => $type) {
            if (array_key_exists($type['type'], $availableTypesArray)) {
                $availableTypesArray[$type['type']] = true;
            }
        }

        $data['types'] = $availableTypesArray;
        $data['image']['name'] = $product->getImage()->getOriginalFileName();

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($productData, $class, $format = null, array $context = [])
    {
        try {
            $imagePath = $this->fileLocator->locate(sprintf('%s/import_export/product_images/%s', $this->rootDir, $productData['image']['name']));
        } catch (\Exception $e) {
            $imagePath = null;
        }

        $productData['image'] = is_array($imagePath) ? current($imagePath) : $imagePath;
        $imageTypes = $this->imageTypeProvider->getImageTypes();

        foreach ($productData['types'] as $type => $value) {
            if (!array_key_exists($type, $imageTypes) || !boolval($value)) {
                unset($imageTypes[$type]);
            }
        }

        $productData['types'] = array_keys($imageTypes);

        return parent::denormalize($productData, $class, $format, $context);
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
