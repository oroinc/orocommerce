# Oro\Bundle\CatalogBundle\Api\Model\CategoryNode

## ACTIONS

### get

Retrieve a specific master catalog tree node record visible to the customer user.

{@inheritdoc}

### get_list

Retrieve a collection of master catalog tree nodes visible to the customer user.

{@inheritdoc}

**Note:** The returned records are sorted by the **order** field.

## FIELDS

### order

A number that can be used to sort nodes to build a node tree in the order
it is configured on the management console.
The less the number, the closer the node to the beginning of the section.

### path

The list of nodes in the path from the root to the current node.

### parent

The parent of the current node in the tree.

### category

The master catalog category associated with the current node in the tree.

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

### category

#### get_subresource

Retrieve a record of master catalog category associated with a specific node record.

#### get_relationship

Retrieve the ID of master catalog category associated with a specific node record.

