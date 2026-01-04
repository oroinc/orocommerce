<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Helper;

class ProductTestHelper
{
    public const TEST_SKU = 'SKU-001';
    public const UPDATED_SKU = 'SKU-001-updated';
    public const FIRST_DUPLICATED_SKU = 'SKU-001-updated-1';
    public const SECOND_DUPLICATED_SKU = 'SKU-001-updated-2';

    public const STATUS = 'Disabled';
    public const UPDATED_STATUS = 'Enabled';

    public const TYPE = 'Simple';

    public const INVENTORY_STATUS = 'In Stock';
    public const UPDATED_INVENTORY_STATUS = 'Out of Stock';

    public const FIRST_UNIT_CODE = 'each';
    public const FIRST_UNIT_FULL_NAME = 'each';
    public const FIRST_UNIT_PRECISION = '0';

    public const SECOND_UNIT_CODE = 'kg';
    public const SECOND_UNIT_FULL_NAME = 'kilogram';
    public const SECOND_UNIT_PRECISION = '1';

    public const THIRD_UNIT_CODE = 'piece';
    public const THIRD_UNIT_FULL_NAME = 'piece';
    public const THIRD_UNIT_PRECISION = '0';

    public const DEFAULT_NAME = 'default name';
    public const DEFAULT_NAME_ALTERED = 'altered default name';
    public const DEFAULT_DESCRIPTION = 'default description';
    public const DEFAULT_SHORT_DESCRIPTION = 'default short description';

    public const CATEGORY_ID = 1;
    public const CATEGORY_MENU_NAME = 'Master Catalog';
    public const CATEGORY_NAME = 'All Products';

    public const FIRST_IMAGE_FILENAME = 'image1.gif';
    public const SECOND_IMAGE_FILENAME = 'image2.gif';

    public const IMAGES_VIEW_BODY_SELECTOR = 'div.image-collection table tbody tr';
    public const IMAGES_VIEW_HEAD_SELECTOR = 'div.image-collection table thead tr th';
    public const IMAGE_TYPE_CHECKED_TAG = 'i';
    public const IMAGE_TYPE_CHECKED_CLASS = 'fa-check-square-o';
    public const IMAGE_FILENAME_ATTR = 'title';

    public const ATTRIBUTE_FAMILY_ID = 1;
}
