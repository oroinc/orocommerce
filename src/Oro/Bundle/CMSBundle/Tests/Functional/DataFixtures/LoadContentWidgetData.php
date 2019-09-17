<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadContentWidgetData extends AbstractFixture implements DependentFixtureInterface
{
    private const CONTENT_WIDGET_1 = 'content_widget_1';
    private const CONTENT_WIDGET_TYPE_1 = 'sample_type';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $contentWidget = new ContentWidget();
        $contentWidget->setWidgetType(self::CONTENT_WIDGET_TYPE_1);
        $contentWidget->setName(self::CONTENT_WIDGET_1);
        $contentWidget->setOrganization($this->getReference('organization'));

        $this->setReference(self::CONTENT_WIDGET_1, $contentWidget);
        $manager->persist($contentWidget);

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
