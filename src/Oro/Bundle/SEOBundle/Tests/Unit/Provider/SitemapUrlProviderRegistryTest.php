<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Provider;

use Oro\Bundle\SEOBundle\Provider\SitemapUrlProviderInterface;
use Oro\Bundle\SEOBundle\Provider\SitemapUrlProviderRegistry;

class SitemapUrlProviderRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SitemapUrlProviderRegistry
     */
    private $registry;

    protected function setUp()
    {
        $this->registry = new SitemapUrlProviderRegistry();
    }

    public function testGetProviders()
    {
        $firstProviderName = 'first_provider';
        $firstProviderMock = $this->getProviderMock();
        $secondProviderName = 'second_provider';
        $secondProviderMock = $this->getProviderMock();
        $this->registry->addProvider($firstProviderMock, $firstProviderName);
        $this->registry->addProvider($secondProviderMock, $secondProviderName);
        
        $this->assertEquals(
            [
                $firstProviderName => $firstProviderMock,
                $secondProviderName => $secondProviderMock,
            ],
            $this->registry->getProviders()
        );
    }
    
    public function testGetProviderNames()
    {
        $firstProviderName = 'first_provider';
        $firstProviderMock = $this->getProviderMock();
        $secondProviderName = 'second_provider';
        $secondProviderMock = $this->getProviderMock();
        $this->registry->addProvider($firstProviderMock, $firstProviderName);
        $this->registry->addProvider($secondProviderMock, $secondProviderName);

        $this->assertEquals(
            [
                $firstProviderName,
                $secondProviderName,
            ],
            $this->registry->getProviderNames()
        );
    }
    
    public function testGetProviderByName()
    {
        $providerName = 'first_provider';
        $providerMock = $this->getProviderMock();
        $this->registry->addProvider($providerMock, $providerName);

        $this->assertEquals(
            $providerMock,
            $this->registry->getProviderByName($providerName)
        );
        $this->assertNull($this->registry->getProviderByName('some_other_name'));
    }

    /**
     * @return SitemapUrlProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getProviderMock()
    {
        return $this->createMock(SitemapUrlProviderInterface::class);
    }
}
