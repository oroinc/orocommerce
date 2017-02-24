<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Provider;

use Oro\Bundle\SEOBundle\Provider\UrlItemsProviderRegistry;
use Oro\Component\SEO\Provider\UrlItemsProviderInterface;

class UrlItemsProviderRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UrlItemsProviderRegistry
     */
    private $registry;

    protected function setUp()
    {
        $this->registry = new UrlItemsProviderRegistry();
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
     * @return UrlItemsProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getProviderMock()
    {
        return $this->createMock(UrlItemsProviderInterface::class);
    }
}
