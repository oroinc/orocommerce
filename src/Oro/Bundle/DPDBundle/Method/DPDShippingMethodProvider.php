<?php

namespace Oro\Bundle\DPDBundle\Method;

use Oro\Bundle\DPDBundle\Cache\ZipCodeRulesCache;
use Oro\Bundle\DPDBundle\Factory\DPDRequestFactory;
use Oro\Bundle\DPDBundle\Provider\ChannelType;
use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\DPDBundle\Provider\RateTablePriceProvider;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Oro\Bundle\DPDBundle\Provider\DPDTransport;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

class DPDShippingMethodProvider implements ShippingMethodProviderInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var DPDTransport
     */
    protected $transportProvider;

    /**
     * @var DPDRequestFactory
     */
    protected $dpdRequestFactory;

    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @var PackageProvider
     */
    protected $packageProvider;

    /**
     * @var RateTablePriceProvider
     */
    protected $rateTablePriceProvider;

    /**
     * @var ZipCodeRulesCache
     */
    protected $zipCodeRulesCache;

    /**
     * @var OrderShippingLineItemConverterInterface
     */
    protected $shippingLineItemConverter;

    /**
     * @var ShippingMethodInterface[]
     */
    protected $methods;

    /**
     * DPDShippingMethodProvider constructor.
     *
     * @param ManagerRegistry                         $doctrine
     * @param DPDTransport                            $transportProvider
     * @param DPDRequestFactory                       $dpdRequestFactory
     * @param LocalizationHelper                      $localizationHelper
     * @param PackageProvider                         $packageProvider
     * @param RateTablePriceProvider                  $rateTablePriceProvider
     * @param ZipCodeRulesCache                       $zipCodeRulesCache
     * @param OrderShippingLineItemConverterInterface $shippingLineItemConverter
     */
    public function __construct(
        ManagerRegistry $doctrine,
        DPDTransport $transportProvider,
        DPDRequestFactory $dpdRequestFactory,
        LocalizationHelper $localizationHelper,
        PackageProvider $packageProvider,
        RateTablePriceProvider $rateTablePriceProvider,
        ZipCodeRulesCache $zipCodeRulesCache,
        OrderShippingLineItemConverterInterface $shippingLineItemConverter
    ) {
        $this->doctrine = $doctrine;
        $this->transportProvider = $transportProvider;
        $this->dpdRequestFactory = $dpdRequestFactory;
        $this->localizationHelper = $localizationHelper;
        $this->packageProvider = $packageProvider;
        $this->rateTablePriceProvider = $rateTablePriceProvider;
        $this->zipCodeRulesCache = $zipCodeRulesCache;
        $this->shippingLineItemConverter = $shippingLineItemConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingMethods()
    {
        if (!$this->methods) {
            $channels = $this->doctrine->getManagerForClass('OroIntegrationBundle:Channel')
                ->getRepository('OroIntegrationBundle:Channel')->findBy([
                    'type' => ChannelType::TYPE,
                ]);
            $this->methods = [];
            /** @var Channel $channel */
            foreach ($channels as $channel) {
                if ($channel->isEnabled()) {
                    $method = new DPDShippingMethod(
                        $this->transportProvider,
                        $channel,
                        $this->dpdRequestFactory,
                        $this->localizationHelper,
                        $this->packageProvider,
                        $this->rateTablePriceProvider,
                        $this->zipCodeRulesCache,
                        $this->shippingLineItemConverter
                    );
                    $this->methods[$method->getIdentifier()] = $method;
                }
            }
        }

        return $this->methods;
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingMethod($name)
    {
        if ($this->hasShippingMethod($name)) {
            return $this->getShippingMethods()[$name];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasShippingMethod($name)
    {
        return array_key_exists($name, $this->getShippingMethods());
    }
}
