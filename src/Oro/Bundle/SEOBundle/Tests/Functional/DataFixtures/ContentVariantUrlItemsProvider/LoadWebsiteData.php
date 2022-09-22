<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures\ContentVariantUrlItemsProvider;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class LoadWebsiteData extends AbstractFixture
{
    public const WEBSITE_DEFAULT = 'website_default';

    public function load(ObjectManager $manager)
    {
        $website = $manager->getRepository(Website::class)->findBy([], ['id' => 'ASC'], 1);
        $this->setReference(self::WEBSITE_DEFAULT, $website[0]);
    }
}
