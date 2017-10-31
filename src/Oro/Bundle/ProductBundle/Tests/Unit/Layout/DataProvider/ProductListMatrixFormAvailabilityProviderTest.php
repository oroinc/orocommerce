<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormAvailabilityProvider;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductListMatrixFormAvailabilityProvider;
use Oro\Bundle\UIBundle\Provider\UserAgentProvider;

class ProductListMatrixFormAvailabilityProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    private $configManager;

    /** @var ProductFormAvailabilityProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $productFormAvailabilityProvider;

    /** @var UserAgentProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $userAgentProvider;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->productFormAvailabilityProvider = $this->createMock(ProductFormAvailabilityProvider::class);
        $this->userAgentProvider = $this->createMock(UserAgentProvider::class);

        $this->provider = new ProductListMatrixFormAvailabilityProvider(
            $this->configManager,
            $this->productFormAvailabilityProvider,
            $this->userAgentProvider
        );
    }


}
