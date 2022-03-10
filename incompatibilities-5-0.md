- [CMSBundle](#cmsbundle)
- [PricingBundle](#pricingbundle)
- [ProductBundle](#productbundle)
- [TaxBundle](#taxbundle)

CMSBundle
---------
* The `DigitalAssetTwigTagsConverter::__construct(ManagerRegistry $managerRegistry, FileUrlProviderInterface $fileUrlProvider)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/5.0.0-rc/src/Oro/Bundle/CMSBundle/Tools/DigitalAssetTwigTagsConverter.php#L22 "Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter")</sup> method was changed to `DigitalAssetTwigTagsConverter::__construct(ManagerRegistry $managerRegistry, FileUrlProviderInterface $fileUrlProvider, $defaultImageFilter)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/5.0.0/src/Oro/Bundle/CMSBundle/Tools/DigitalAssetTwigTagsConverter.php#L24 "Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter")</sup>
* The `PreviewMetadataProvider::__construct(PreviewMetadataProviderInterface $innerPreviewMetadataProvider, FileUrlProviderInterface $fileUrlProvider, MimeTypeChecker $mimeTypeChecker)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/5.0.0-rc/src/Oro/Bundle/CMSBundle/Provider/PreviewMetadataProvider.php#L25 "Oro\Bundle\CMSBundle\Provider\PreviewMetadataProvider")</sup> method was changed to `PreviewMetadataProvider::__construct(PreviewMetadataProviderInterface $innerPreviewMetadataProvider, FileUrlProviderInterface $fileUrlProvider, MimeTypeChecker $mimeTypeChecker, MimeTypesInterface $mimeTypes)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/5.0.0/src/Oro/Bundle/CMSBundle/Provider/PreviewMetadataProvider.php#L24 "Oro\Bundle\CMSBundle\Provider\PreviewMetadataProvider")</sup>

PricingBundle
-------------
* The following methods in class `ShardManager`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/5.0.0-rc/src/Oro/Bundle/PricingBundle/Sharding/ShardManager.php#L400 "Oro\Bundle\PricingBundle\Sharding\ShardManager")</sup> were removed:
   - `serialize`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/5.0.0-rc/src/Oro/Bundle/PricingBundle/Sharding/ShardManager.php#L400 "Oro\Bundle\PricingBundle\Sharding\ShardManager::serialize")</sup>
   - `unserialize`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/5.0.0-rc/src/Oro/Bundle/PricingBundle/Sharding/ShardManager.php#L408 "Oro\Bundle\PricingBundle\Sharding\ShardManager::unserialize")</sup>

ProductBundle
-------------
* The `ProductImageFileNameProvider::getFileName(File $file, $format)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/5.0.0-rc/src/Oro/Bundle/ProductBundle/Provider/ProductImageFileNameProvider.php#L36 "Oro\Bundle\ProductBundle\Provider\ProductImageFileNameProvider")</sup> method was changed to `ProductImageFileNameProvider::getFileName(File $file)`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/5.0.0/src/Oro/Bundle/ProductBundle/Provider/ProductImageFileNameProvider.php#L30 "Oro\Bundle\ProductBundle\Provider\ProductImageFileNameProvider")</sup>

TaxBundle
---------
* The `Result::serialize`<sup>[[?]](https://github.com/oroinc/orocommerce/tree/5.0.0-rc/src/Oro/Bundle/TaxBundle/Model/Result.php#L114 "Oro\Bundle\TaxBundle\Model\Result::serialize")</sup> method was removed.

