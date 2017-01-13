<?php
namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Config;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
abstract class AbstractPaymentConfigTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentConfigInterface
     */
    protected $config;

    /**
     * @var SymmetricCrypterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $encoder;

    /**
     * @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localizationHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->encoder = $this->createMock(SymmetricCrypterInterface::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->config = $this->getPaymentConfig();
    }

    /**
     * @return PaymentConfigInterface
     */
    abstract protected function getPaymentConfig();

    public function testGetLabel()
    {
        $returnValue = 'test label';
        $label = (new LocalizedFallbackValue())->setString($returnValue);
        $labels = new ArrayCollection();
        $labels->add($label);
        $this->localizationHelper->expects(static::once())
            ->method('getLocalizedValue')
            ->with($labels)
            ->willReturn($returnValue);
        $this->assertSame($returnValue, $this->config->getLabel());
    }

    public function testGetShortLabel()
    {
        $returnValue = 'test short label';
        $label = (new LocalizedFallbackValue())->setString($returnValue);
        $labels = new ArrayCollection();
        $labels->add($label);
        $this->localizationHelper->expects(static::once())
            ->method('getLocalizedValue')
            ->with($labels)
            ->willReturn($returnValue);
        $this->assertSame($returnValue, $this->config->getShortLabel());
    }
}
