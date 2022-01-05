# Oro\Bundle\ProductBundle\Entity\ProductImage

## ACTIONS

### get

Retrieve a specific product image record.

{@inheritdoc}

### get_list

Retrieve a collection of product image records.

{@inheritdoc}

## FIELDS

### mimeType

A media type and subtype of the image.

### files

An array contains information about resized versions of the image.

Each element of the array is an object with the following properties:

**url** is a string that contains URL of the image.

**maxWidth** is a image maximum width. Can be an integer value, a string "auto" or `null`.

**maxHeight** is an image maximum height. Can be an integer value, a string "auto" or `null`.

**dimension** is a string that contains the name of a filter applied to the image.

**types** is an array of types to which the image belongs to.

**url_webp** is a string that contains URL of the image in WebP format.

Example of data:

- "disabled" or "for_all" WebP strategy is enabled: **\[{"url": "/path/to/image.jpeg", "maxWidth": 610, "maxHeight": "auto", "dimension": "product_gallery_popup", "types": \["main"\]}\]**

- "if_supported" WebP strategy is enabled: **\[{"url": "/path/to/image.jpeg", "maxWidth": 610, "maxHeight": "auto", "dimension": "product_gallery_popup", "types": \["main"\], "url_webp": "/path/to/image.jpeg.webp"}\]**

### product

The product to which this image belongs to.

### types

An array of types to which this image belongs to.

Example of data: **\["main", "listing", "additional"\]**

## SUBRESOURCES

### product

#### get_subresource

Retrieve the product a specific product image record belongs to.

#### get_relationship

Retrieve the ID of the product for a specific product image record belongs to.
