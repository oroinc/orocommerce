<?php

namespace Oro\Bundle\ProductBundle\Layout\Mapper;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Layout\Mapper\AttributeBlockTypeMapperInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

class AttributeBlockTypeMapper implements AttributeBlockTypeMapperInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws \LogicException
     */
    public function getBlockType(FieldConfigModel $attribute)
    {
        if ($attribute->getType() === Product::class) {
            return 'attribute_images';
        }

        return null;
    }
}
