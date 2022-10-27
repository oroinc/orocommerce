<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\Test;

use Oro\Bundle\WebsiteBundle\Tests\Functional\Stub\WebsiteManagerStub;
use Psr\Container\ContainerInterface;

/**
 * Provides methods to change the website in functional tests.
 * It is expected that this trait will be used in classes
 * derived from {@see \Oro\Bundle\TestFrameworkBundle\Test\WebTestCase}.
 * IMPORTANT: you must add the following method in the class that include this trait:
 * <code>
 *    /**
 *     * @beforeResetClient
 *     *\/
 *    public static function afterFrontendTest()
 *    {
 *        $this->getWebsiteManagerStub()->disableStub();
 *    }
 * </code>
 *
 * @method static ContainerInterface getContainer()
 */
trait WebsiteManagerTrait
{
    /**
     * @param string $websiteReference
     */
    protected function setCurrentWebsite($websiteReference = null)
    {
        $websiteManagerStub = self::getWebsiteManagerStub();

        $websiteManagerStub->disableStub();
        $defaultWebsite = $websiteManagerStub->getDefaultWebsite();
        if (!$websiteReference || $websiteReference === 'default') {
            $website = $defaultWebsite;
        } else {
            if (!$this->hasReference($websiteReference)) {
                throw new \RuntimeException(
                    sprintf('The website reference "%s" was not found.', $websiteReference)
                );
            }
            $website = $this->getReference($websiteReference);
        }

        $websiteManagerStub->enableStub();
        $websiteManagerStub->setCurrentWebsiteStub($website);
        $websiteManagerStub->setDefaultWebsiteStub($defaultWebsite);
    }

    protected static function getDefaultWebsiteId(): int
    {
        return self::getWebsiteManagerStub()->getDefaultWebsite()->getId();
    }

    private static function getWebsiteManagerStub(): WebsiteManagerStub
    {
        $manager = self::getContainer()->get('oro_website.manager');
        if (!$manager instanceof WebsiteManagerStub) {
            throw new \LogicException(sprintf(
                'The service "oro_website.manager" should be instance of "%s", given "%s".',
                WebsiteManagerStub::class,
                get_class($manager)
            ));
        }

        return $manager;
    }
}
