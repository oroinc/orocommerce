<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\ContentWidgetUsage;

class LoadContentWidgetUsageData extends AbstractFixture implements DependentFixtureInterface
{
    public const CONTENT_WIDGET_USAGE_1_A = 'content_widget_usage.1';
    public const CONTENT_WIDGET_USAGE_1_B = 'content_widget_usage.2';
    public const CONTENT_WIDGET_USAGE_2_A = 'content_widget_usage.3';

    public const CONTENT_WIDGET_USAGES
        = [
            self::CONTENT_WIDGET_USAGE_1_A => [
                'widget' => LoadContentWidgetData::CONTENT_WIDGET_1,
                'class' => \stdClass::class,
                'id' => 1,
                'field' => 'field_a',
            ],
            self::CONTENT_WIDGET_USAGE_1_B => [
                'widget' => LoadContentWidgetData::CONTENT_WIDGET_2,
                'class' => \stdClass::class,
                'id' => 1,
                'field' => 'field_b',
            ],
            self::CONTENT_WIDGET_USAGE_2_A => [
                'widget' => LoadContentWidgetData::CONTENT_WIDGET_3,
                'class' => \stdClass::class,
                'id' => 2,
                'field' => 'field_a',
            ],
        ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::CONTENT_WIDGET_USAGES as $reference => $mapping) {
            $contentWidgetUsage = new ContentWidgetUsage();
            $contentWidgetUsage->setContentWidget($this->getReference($mapping['widget']));
            $contentWidgetUsage->setEntityClass($mapping['class']);
            $contentWidgetUsage->setEntityId($mapping['id']);
            $contentWidgetUsage->setEntityField($mapping['field']);

            $this->setReference($reference, $contentWidgetUsage);
            $manager->persist($contentWidgetUsage);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadContentWidgetData::class,
        ];
    }
}
