UPGRADE FROM 1.1 to 1.2
=======================================

LayoutBundle
-------------
 - `isApplicable(ThemeImageTypeDimension $dimension)` method added to `Oro\Bundle\LayoutBundle\Provider\CustomImageFilterProviderInterface`

AttachmentBundle
-------------
 - `Oro\Bundle\AttachmentBundle\Resizer\ImageResizer` is now responsible for image resizing only. Use `Oro\Bundle\AttachmentBundle\Manager\MediaCacheManager` to store resized images.
 - `ImageResizer::resizeImage(File $image, $filterName)` has 2 parameters only now.
