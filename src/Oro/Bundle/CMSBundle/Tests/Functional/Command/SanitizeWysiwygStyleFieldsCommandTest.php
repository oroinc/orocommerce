<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Command;

use Oro\Bundle\CMSBundle\Command\SanitizeWysiwygStyleFieldsCommand;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadTagsIntoWysiwygStyleFields;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Command\CommandTestingTrait;

class SanitizeWysiwygStyleFieldsCommandTest extends WebTestCase
{
    use CommandTestingTrait;

    private const EXPECTED_CSS = 'body { color: black; }';

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([LoadTagsIntoWysiwygStyleFields::class]);
    }

    public function testExecuteWithoutForce(): void
    {
        $commandTester = $this->doExecuteCommand(SanitizeWysiwygStyleFieldsCommand::getDefaultName());

        self::assertNotSame(0, $commandTester->getStatusCode());
        $this->assertOutputContains(
            $commandTester,
            'To force execution run command with --force option'
        );
    }

    public function testExecuteWithDryRun(): void
    {
        $commandTester = $this->doExecuteCommand(
            SanitizeWysiwygStyleFieldsCommand::getDefaultName(),
            ['--dry-run' => true]
        );

        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains($commandTester, 'Products (Oro\Bundle\ProductBundle\Entity\Product)');
        $this->assertOutputContains(
            $commandTester,
            sprintf(
                '/product/update/%d, affected field(s): Description',
                $this->getReference(LoadProductData::PRODUCT_1)->getId()
            )
        );
        $this->assertOutputContains($commandTester, 'Landing Pages (Oro\Bundle\CMSBundle\Entity\Page)');
        $this->assertOutputContains(
            $commandTester,
            sprintf(
                '/cms/page/update/%d, affected field(s): Content',
                $this->getReference(LoadPageData::PAGE_1)->getId()
            )
        );
        $this->assertOutputContains($commandTester, 'Content Blocks (Oro\Bundle\CMSBundle\Entity\ContentBlock)');
        $this->assertOutputContains(
            $commandTester,
            sprintf(
                '/cms/content-block/update/%d, affected field(s): Content Variant',
                $this->getReference('content_block_1')->getId()
            )
        );
        $this->assertOutputContains($commandTester, '3 occurrences across 3 entities that need sanitizing were found.');
    }

    public function testExecuteWithForceAndEntityClass(): void
    {
        $commandTester = $this->doExecuteCommand(
            SanitizeWysiwygStyleFieldsCommand::getDefaultName(),
            ['--force' => true, '--entity-class' => Product::class]
        );

        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains($commandTester, 'Products (Oro\Bundle\ProductBundle\Entity\Product)');

        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $this->assertOutputContains(
            $commandTester,
            sprintf(
                '/product/update/%d, affected field(s): Description',
                $product1->getId()
            )
        );
        $this->assertOutputContains($commandTester, '1 occurrences across 1 entities were sanitized.');
        self::assertEquals(self::EXPECTED_CSS, (string)$product1->getDescription()->getWysiwygStyle());
    }

    public function testExecuteWithForce(): void
    {
        $commandTester = $this->doExecuteCommand(
            SanitizeWysiwygStyleFieldsCommand::getDefaultName(),
            ['--force' => true]
        );

        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains($commandTester, 'Landing Pages (Oro\Bundle\CMSBundle\Entity\Page)');
        $page1 = $this->getReference(LoadPageData::PAGE_1);
        $this->assertOutputContains(
            $commandTester,
            sprintf(
                '/cms/page/update/%d, affected field(s): Content',
                $page1->getId()
            )
        );
        self::assertEquals(self::EXPECTED_CSS, (string)$page1->getContentStyle());

        $this->assertOutputContains($commandTester, 'Content Blocks (Oro\Bundle\CMSBundle\Entity\ContentBlock)');
        $this->assertOutputContains(
            $commandTester,
            sprintf(
                '/cms/content-block/update/%d, affected field(s): Content Variant',
                $this->getReference('content_block_1')->getId()
            )
        );
        $this->assertOutputContains($commandTester, '2 occurrences across 2 entities were sanitized.');
        self::assertEquals(
            self::EXPECTED_CSS,
            (string)$this->getReference('text_content_variant1')->getContentStyle()
        );
    }

    public function testExecuteWithDryRunAndNothingToSanitize(): void
    {
        $commandTester = $this->doExecuteCommand(
            SanitizeWysiwygStyleFieldsCommand::getDefaultName(),
            ['--dry-run' => true]
        );

        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains($commandTester, 'Entities that need sanitizing were not found.');
    }
}
