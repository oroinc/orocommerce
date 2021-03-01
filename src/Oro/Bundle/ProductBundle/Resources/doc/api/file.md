# Oro\Bundle\AttachmentBundle\Entity\File

## FIELDS

### filePath

Relative URLs of the resized versions of the image.

The object property name is a resized image type.
The object property value is a relative URL.

Possible image types: **product_original**, **product_gallery_popup**, **product_gallery_main**,
**product_large**, **product_extra_large**, **product_medium**, **product_small**.

Example of data: **{"product_original": "/media/cache/attachment/resize/product_original/11c00c6d0bd6b875afe655d3c9d4f942/32/5edcad2383a42535224399.jpeg", "product_large": "/media/cache/attachment/resize/product_large/f8ad5f04db8a20c593bca34d27fd6799/32/5edcad2383a42535224399.jpeg"}**

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**
