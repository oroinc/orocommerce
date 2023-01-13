<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\WYSIWYG;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\ContentWidgetUsage;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Entity\Repository\ContentWidgetUsageRepository;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadContentWidgetData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @group CommunityEdition
 */
class ContentWidgetTwigFunctionProcessorTest extends WebTestCase
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var ContentWidgetUsageRepository */
    private $usageRepository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadContentWidgetData::class]);

        $this->getOptionalListenerManager()->enableListener('oro_cms.event_listener.wysiwyg_field_twig_listener');

        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->usageRepository = $this->em->getRepository(ContentWidgetUsage::class);
    }

    public function testPostPersist(): Page
    {
        /** @var ContentWidget $contentWidget1 */
        $contentWidget1 = $this->getReference(LoadContentWidgetData::CONTENT_WIDGET_1);

        /** @var ContentWidget $contentWidget2 */
        $contentWidget2 = $this->getReference(LoadContentWidgetData::CONTENT_WIDGET_2);

        /** @var ContentWidget $contentWidget3 */
        $contentWidget3 = $this->getReference(LoadContentWidgetData::CONTENT_WIDGET_3);

        $page = new Page();
        $page->setDefaultTitle('testTitle');
        $page->setContent(
            "<div>
                {{ widget('" . $contentWidget1->getName() . "') }}
                {{ widget('" . $contentWidget2->getName() . "') }}
            </div>"
        );

        // check not performed style field
        $page->setContentStyle(
            ".test {
                backgroud-image: url({{ widget('" . $contentWidget3->getName() . "') }})
            }"
        );

        $this->em->persist($page);

        $this->assertContentWidgets($page, [
            $contentWidget1->getId() => $contentWidget1,
            $contentWidget2->getId() => $contentWidget2,
        ]);

        return $page;
    }

    /**
     * @depends testPostPersist
     */
    public function testPreUpdate(Page $page): Page
    {
        /** @var ContentWidget $contentWidget1 */
        $contentWidget1 = $this->getReference(LoadContentWidgetData::CONTENT_WIDGET_1);

        /** @var ContentWidget $contentWidget2 */
        $contentWidget2 = $this->getReference(LoadContentWidgetData::CONTENT_WIDGET_2);

        /** @var ContentWidget $contentWidget3 */
        $contentWidget3 = $this->getReference(LoadContentWidgetData::CONTENT_WIDGET_3);

        $page->setContent(
            "<div>
                {{ widget('" . $contentWidget1->getName() . "') }}
                {{ widget('" . $contentWidget3->getName() . "') }}
            </div>"
        );

        // check not performed style field
        $page->setContentStyle(
            ".test {
                backgroud-image: url({{ widget('" . $contentWidget2->getName() . "') }})
            }"
        );

        $this->assertContentWidgets($page, [
            $contentWidget1->getId() => $contentWidget1,
            $contentWidget3->getId() => $contentWidget3,
        ]);

        return $page;
    }

    /**
     * @depends testPreUpdate
     */
    public function testRemove(Page $page): void
    {
        $this->em->remove($page);

        $this->assertContentWidgets($page, []);
    }

    private function assertContentWidgets(Page $page, array $contentWidgets): void
    {
        $this->em->flush();
        $this->getContainer()->get('oro_cms.tests.event_listener.wysiwyg_field_twig_listener')->onTerminate();

        /** @var ContentWidgetUsage[] $usages */
        $usages = $this->usageRepository->findBy([
            'entityClass' => Page::class,
        ]);

        $this->assertCount(count($contentWidgets), $usages);

        foreach ($usages as $usage) {
            $this->assertArrayHasKey($usage->getContentWidget()->getId(), $contentWidgets);
            $this->assertEquals($contentWidgets[$usage->getContentWidget()->getId()], $usage->getContentWidget());
            unset($contentWidgets[$usage->getContentWidget()->getId()]);

            $this->assertSame(Page::class, $usage->getEntityClass());
            $this->assertSame($page->getId(), $usage->getEntityId());
            $this->assertSame('content', $usage->getEntityField());
        }
    }
}
