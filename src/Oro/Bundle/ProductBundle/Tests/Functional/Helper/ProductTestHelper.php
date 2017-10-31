<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Helper;

class ProductTestHelper
{
    const TEST_SKU = 'SKU-001';
    const UPDATED_SKU = 'SKU-001-updated';
    const FIRST_DUPLICATED_SKU = 'SKU-001-updated-1';
    const SECOND_DUPLICATED_SKU = 'SKU-001-updated-2';

    const STATUS = 'Disabled';
    const UPDATED_STATUS = 'Enabled';

    const TYPE = 'Simple';

    const INVENTORY_STATUS = 'In Stock';
    const UPDATED_INVENTORY_STATUS = 'Out of Stock';

    const FIRST_UNIT_CODE = 'each';
    const FIRST_UNIT_FULL_NAME = 'each';
    const FIRST_UNIT_PRECISION = '0';

    const SECOND_UNIT_CODE = 'kg';
    const SECOND_UNIT_FULL_NAME = 'kilogram';
    const SECOND_UNIT_PRECISION = '1';

    const THIRD_UNIT_CODE = 'piece';
    const THIRD_UNIT_FULL_NAME = 'piece';
    const THIRD_UNIT_PRECISION = '0';

    const DEFAULT_NAME = 'default name';
    const DEFAULT_NAME_ALTERED = 'altered default name';
    const DEFAULT_DESCRIPTION = 'default description';
    const DEFAULT_SHORT_DESCRIPTION = 'default short description';

    const CATEGORY_ID = 1;
    const CATEGORY_MENU_NAME = 'Master Catalog';
    const CATEGORY_NAME = 'All Products';

    const FIRST_IMAGE_FILENAME = 'image1.gif';
    const SECOND_IMAGE_FILENAME = 'image2.gif';

    const IMAGES_VIEW_BODY_SELECTOR = 'div.image-collection table tbody tr';
    const IMAGES_VIEW_HEAD_SELECTOR = 'div.image-collection table thead tr th';
    const IMAGE_TYPE_CHECKED_TAG = 'i';
    const IMAGE_TYPE_CHECKED_CLASS = 'fa-check-square-o';
    const IMAGE_FILENAME_ATTR = 'title';

    const ATTRIBUTE_FAMILY_ID = 1;
}
