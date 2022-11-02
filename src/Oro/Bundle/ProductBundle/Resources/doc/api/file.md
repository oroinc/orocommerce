# Oro\Bundle\AttachmentBundle\Entity\File

## FIELDS

### filePath

Relative URLs of the resized versions of the image.

Each element of the array is an object with the following properties:

**url** is a relative URL to the resized image;

**dimension** is a resized image type;

**url_webp** is a relative URL to the resized image in WebP format if configuration "oro_attachment.webp_strategy" is "if_supported".

Possible image types: **product_original**, **product_gallery_popup**, **product_gallery_main**,
**product_large**, **product_extra_large**, **product_medium**, **product_small**.

Example of data:

- "disabled" or "for_all" WebP strategy is enabled: **{{"url": "/media/cache/attachment/resize/product_original/11c00c6d0bd6b875afe655d3c9d4f942/32/5edcad2383a42535224399.jpeg", "dimension": "product_original"}, {"url": "/media/cache/attachment/resize/product_large/f8ad5f04db8a20c593bca34d27fd6799/32/5edcad2383a42535224399.jpeg", "dimension": "product_large"}}**

- "if_supported" WebP strategy is enabled: **{{"url": "/media/cache/attachment/resize/product_original/11c00c6d0bd6b875afe655d3c9d4f942/32/5edcad2383a42535224399.jpeg", "dimension": "product_original", "url_webp": "/media/cache/attachment/resize/product_original/11c00c6d0bd6b875afe655d3c9d4f942/32/5edcad2383a42535224399.jpeg.webp"}, {"url": "/media/cache/attachment/resize/product_large/f8ad5f04db8a20c593bca34d27fd6799/32/5edcad2383a42535224399.jpeg", "dimension": "product_large", "url_webp": "/media/cache/attachment/resize/product_large/f8ad5f04db8a20c593bca34d27fd6799/32/5edcad2383a42535224399.jpeg.webp"}}**


#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**
