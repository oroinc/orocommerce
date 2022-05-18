<?php
declare(strict_types=1);

namespace Oro\Bundle\SEOBundle\Tests\Functional\Command;

use Oro\Bundle\LocaleBundle\Command\CleanupUnusedLocalizedFallbackValuesCommand;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures\LoadProductMetaData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CleanupUnusedLocalizedFallbackValuesCommandTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadProductMetaData::class]);
    }

    public function testMetaFieldsDuplicateProcessShouldNotCreateUnusedLocalizedFallbackValues(): void
    {
        $result = self::runCommand(CleanupUnusedLocalizedFallbackValuesCommand::getDefaultName());

        self::assertStringContainsString('Removing unused localized fallback values completed.', $result);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        self::getContainer()->get('oro_product.service.duplicator')->duplicate($product);

        $result = self::runCommand(CleanupUnusedLocalizedFallbackValuesCommand::getDefaultName());

        self::assertStringContainsString(
            'Removing unused localized fallback values completed. Deleted: 0 records.',
            $result
        );
    }
}
