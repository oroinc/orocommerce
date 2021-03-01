# Oro\Bundle\WebCatalogBundle\Entity\ContentNode

## ACTIONS

### get

Retrieve a specific web catalog tree node record visible to the customer user.

{@inheritdoc}

### get_list

Retrieve a collection of web catalog tree nodes visible to the customer user.

{@inheritdoc}

**Note:** The returned records are sorted by the **order** field.

## FIELDS

### title

The localized title of the node.

### level

A number that indicates a nesting level of a node. For the root node the level is **0**,
for nodes belong to the root node the level is **1**, and so on.

### order

A number that can be used to sort nodes to build a node tree in the order
it is configured on the management console.
The less the number, the closer the node to the beginning of the section.

### url

The relative URL of the node for the current localization.

### urls

An array of node urls for all localizations except the current localization.

Each element of the array is an object with the following properties:

**url** is a string that contains the relative URL of the node.

**localizationId** is a string that contains ID of the localization the url is intended for.

Example of data: **\[{"url": "/en-url", "localizationId": "10"}, {"url": "/fr-url", "localizationId": "11"}\]**

### path

The list of nodes in the path from the root to the current node.

### parent

The parent of the current node in the tree.

### content

The content variant for the node, e.g. a system page, a landing page, a master catalog category, a product,
a product collection, etc.

## FILTERS

### root

Filter child nodes for a node with the specified ID. The child nodes are returned independs on the nesting level of them. Use 'gt' operator to get child nodes without the specified node. Use 'gte' operator to get the specified node and its child nodes.

## SUBRESOURCES

### path

#### get_subresource

Retrieve the list of nodes in the path from the root to the specific node.

#### get_relationship

Retrieve the IDs of nodes in the path from the root to the specific node.

### parent

#### get_subresource

Retrieve a record of parent node assigned to a specific node record.

#### get_relationship

Retrieve the ID of parent node record assigned to a specific node record.

### content

#### get_subresource

Retrieve a record of content variant for a specific node record.

#### get_relationship

Retrieve the ID of content variant record for a specific node record.
