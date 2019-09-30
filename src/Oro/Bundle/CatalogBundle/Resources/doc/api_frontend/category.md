# Oro\Bundle\CatalogBundle\Entity\Category

## ACTIONS

### get

Retrieve a specific master catalog category record visible to the customer user.

{@inheritdoc}

### get_list

Retrieve a collection of master catalog categories visible to the customer user.

{@inheritdoc}

## FIELDS

### title

The localized title of the category.

### shortDescription

The localized short description of the category.

### description

The localized description of the category.

### url

The relative URL of the category for the current localization.

### urls

An array of category urls for all localizations except the current localization.

Each element of the array is an object with the following properties:

**url** is a string that contains the relative URL of the category.

**localizationId** is a string that contains ID of the localization the url is intended for.

Example of data: **\[{"url": "/en-url", "localizationId": "10"}, {"url": "/fr-url", "localizationId": "11"}\]**

### images

An array of category images.

Each element of the array is an object with the following properties:

**mimeType** is a string that contains the media type of the image.

**url** is a string that contains URL of the image.

**type** is a string that contains the type of the image. Possible values of the image types are `small` and `large`.

Example of data: **\[{"mimeType:"image/jpeg", "url": "/path/to/image.jpeg", "type": "small"}, {"mimeType:"image/jpeg", "url": "/path/to/image.jpeg", "type": "large"}\]**

### categoryPath

The list of visible categories in the path from master catalog root to the current category.

## SUBRESOURCES

### childCategories

#### get_subresource

Retrieve the child category records assigned to a specific category record.

#### get_relationship

Retrieve the IDs of the child category records assigned to a specific category record.

### categoryPath

#### get_subresource

Retrieve the list of visible categories in the path from master catalog root to the specific category.

#### get_relationship

Retrieve the IDs of visible categories in the path from master catalog root to the specific category.
