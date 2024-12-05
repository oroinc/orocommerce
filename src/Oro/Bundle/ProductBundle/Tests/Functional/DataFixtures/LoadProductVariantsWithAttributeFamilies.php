<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

class LoadProductVariantsWithAttributeFamilies extends LoadProductVariants
{
    public function getDependencies()
    {
        return [LoadProductDataWithAttributeFamilies::class];
    }
}
