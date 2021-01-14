<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadContentWidgetData extends AbstractFixture implements DependentFixtureInterface
{
    public const CONTENT_WIDGET_1 = 'content_widget.1';
    public const CONTENT_WIDGET_2 = 'content_widget.2';
    public const CONTENT_WIDGET_3 = 'content_widget.3';

    /** @var array */
    private static $contentWidgets = [
        self::CONTENT_WIDGET_1,
        self::CONTENT_WIDGET_2,
        self::CONTENT_WIDGET_3,
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$contentWidgets as $contentWidgetName) {
            $contentWidget = new ContentWidget();
            $contentWidget->setWidgetType('test_type');
            $contentWidget->setName($contentWidgetName);
            $contentWidget->setOrganization($this->getReference('organization'));

            $this->setReference($contentWidgetName, $contentWidget);
            $manager->persist($contentWidget);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadOrganization::class,
        ];
    }
}
