<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetRenderer;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\ContentWidgetUsage;
use Oro\Bundle\CMSBundle\Entity\EntityListener\AbstractContentAwareEntityListener;
use Oro\Bundle\CMSBundle\Entity\Repository\ContentWidgetUsageRepository;
use Oro\Bundle\CMSBundle\Parser\TwigContentWidgetParser;
use Oro\Bundle\CMSBundle\Twig\WidgetExtension;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

abstract class ContentAwareEntityListenerTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var EntityRepository */
    private $contentWidgetRepository;

    /** @var ContentWidgetUsageRepository */
    private $contentWidgetUsageRepository;

    /** @var AbstractContentAwareEntityListener */
    private $listener;

    protected function setUp(): void
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addExtension(new WidgetExtension($this->createMock(ContentWidgetRenderer::class)));

        $this->contentWidgetRepository = $this->createMock(EntityRepository::class);
        $this->contentWidgetRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturnCallback(
                static function (array $criteria) {
                    if ($criteria['name'] === 'test1') {
                        return null;
                    }

                    $contentWidget = new ContentWidget();
                    $contentWidget->setName($criteria['name']);

                    return $contentWidget;
                }
            );

        $this->contentWidgetUsageRepository = $this->createMock(ContentWidgetUsageRepository::class);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturnMap(
                [
                    [ContentWidget::class, $this->contentWidgetRepository],
                    [ContentWidgetUsage::class, $this->contentWidgetUsageRepository],
                ]
            );
        $doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($this->isInstanceOf($this->getEntityClass()))
            ->willReturn(42);

        $listenerClass = $this->getEntityListenerClass();

        $this->listener = new $listenerClass(
            new TwigContentWidgetParser($twig),
            $doctrineHelper,
            new PropertyAccessor
        );
    }

    /**
     * @return string
     */
    abstract protected function getEntityListenerClass(): string;

    /**
     * @return string
     */
    abstract protected function getEntityClass(): string;

    public function testPostPersist(): void
    {
        $entity = $this->getEntity("{{ widget('test1') }}{{ widget('test2') }}");

        $contentWidget = new ContentWidget();
        $contentWidget->setName('test2');

        $this->contentWidgetUsageRepository->expects($this->once())
            ->method('add')
            ->with($this->getEntityClass(), 42, $contentWidget);

        $this->contentWidgetUsageRepository->expects($this->never())
            ->method('remove');

        $this->listener->postPersist($entity);
    }

    public function testPreUpdate(): void
    {
        $entity = $this->getEntity("{{ widget('test3') }}{{ widget('test4') }}");

        $contentWidget2 = new ContentWidget();
        $contentWidget2->setName('test2');

        $contentWidget3 = new ContentWidget();
        $contentWidget3->setName('test3');

        $contentWidget4 = new ContentWidget();
        $contentWidget4->setName('test4');

        $this->contentWidgetUsageRepository->expects($this->once())
            ->method('remove')
            ->with($this->getEntityClass(), 42, $contentWidget2);

        $this->contentWidgetUsageRepository->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [$this->getEntityClass(), 42, $contentWidget3],
                [$this->getEntityClass(), 42, $contentWidget4]
            );

        $changeSet = [
            'content' => ["{{ widget('test1') }}{{ widget('test2') }}", "{{ widget('test3') }}{{ widget('test4') }}"]
        ];

        $this->listener->preUpdate(
            $entity,
            new PreUpdateEventArgs($entity, $this->createMock(EntityManager::class), $changeSet)
        );
    }

    public function testPreRemove(): void
    {
        $entity = $this->getEntity("{{ widget('test1') }}{{ widget('test2') }}");

        $this->contentWidgetUsageRepository->expects($this->never())
            ->method('add');

        $this->contentWidgetUsageRepository->expects($this->once())
            ->method('remove')
            ->with($this->getEntityClass(), 42, null);

        $this->listener->preRemove($entity);
    }

    /**
     * @param string $content
     * @return object
     */
    private function getEntity(string $content)
    {
        $class = $this->getEntityClass();

        $entity = new $class();
        $entity->setContent($content);

        return $entity;
    }
}
