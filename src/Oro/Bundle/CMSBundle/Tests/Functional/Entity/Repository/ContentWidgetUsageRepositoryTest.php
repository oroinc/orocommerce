<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CMSBundle\Entity\ContentWidgetUsage;
use Oro\Bundle\CMSBundle\Entity\Repository\ContentWidgetUsageRepository;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadContentWidgetUsageData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ContentWidgetUsageRepositoryTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadContentWidgetUsageData::class]);
    }

    private function getRepository(): ContentWidgetUsageRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(ContentWidgetUsage::class);
    }

    private function getContentWidgetUsage(string $reference): ContentWidgetUsage
    {
        return $this->getReference($reference);
    }

    private function getContentWidgetUsageIds(array $contentWidgetUsages): array
    {
        $ids = array_map(function (ContentWidgetUsage $contentWidgetUsage) {
            return $contentWidgetUsage->getId();
        }, $contentWidgetUsages);
        sort($ids);

        return $ids;
    }

    public function testFindForEntity(): void
    {
        $foundContentWidgetUsages = $this->getRepository()->findForEntityField(\stdClass::class, 1);
        $expectedContentWidgetUsages = [
            $this->getContentWidgetUsage(LoadContentWidgetUsageData::CONTENT_WIDGET_USAGE_1_A),
            $this->getContentWidgetUsage(LoadContentWidgetUsageData::CONTENT_WIDGET_USAGE_1_B)
        ];
        $this->assertSame(
            $this->getContentWidgetUsageIds($expectedContentWidgetUsages),
            $this->getContentWidgetUsageIds($foundContentWidgetUsages)
        );
    }

    public function testFindForEntityField(): void
    {
        $foundContentWidgetUsages = $this->getRepository()->findForEntityField(\stdClass::class, 1, 'field_a');
        $expectedContentWidgetUsages = [
            $this->getContentWidgetUsage(LoadContentWidgetUsageData::CONTENT_WIDGET_USAGE_1_A)
        ];
        $this->assertSame(
            $this->getContentWidgetUsageIds($expectedContentWidgetUsages),
            $this->getContentWidgetUsageIds($foundContentWidgetUsages)
        );
    }
}
