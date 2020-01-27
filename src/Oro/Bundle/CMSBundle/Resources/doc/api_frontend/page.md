# Oro\Bundle\CMSBundle\Entity\Page

## ACTIONS

### get

Retrieve a specific landing page record.

{@inheritdoc}

### get_list

Retrieve a collection of landing page records.

{@inheritdoc}

## FIELDS

### title

The localized title of the landing page.

### content

The content of the landing page.

### content_style

The styles of the landing page.

### content_properties

The properties of the landing page.

### url

The relative URL of the landing page for the current localization.

### urls

An array of landing page urls for all localizations except the current localization.

Each element of the array is an object with the following properties:

**url** is a string that contains the relative URL of the landing page.

**localizationId** is a string that contains ID of the localization the url is intended for.

Example of data: **\[{"url": "/en-url", "localizationId": "10"}, {"url": "/fr-url", "localizationId": "11"}\]**

### draftSource

A landing page record.

### draftUuid

The GUID of the landing page draft.

## SUBRESOURCES

### draftSource

#### get_subresource

Retrieve a landing page record.

#### get_relationship

Retrieve the ID of a landing page.
