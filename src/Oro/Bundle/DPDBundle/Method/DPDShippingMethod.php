<?php

namespace Oro\Bundle\DPDBundle\Method;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Factory\DPDRequestFactory;
use Oro\Bundle\DPDBundle\Form\Type\DPDShippingMethodOptionsType;
use Oro\Bundle\DPDBundle\Model\SetOrderResponse;
use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\PricesAwareShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingTrackingAwareInterface;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Provider\DPDTransport as DPDTransportProvider;

class DPDShippingMethod implements
    ShippingMethodInterface,
    ShippingTrackingAwareInterface,
    PricesAwareShippingMethodInterface
{
    const IDENTIFIER = 'dpd';
    const TRACKING_URL = 'https://tracking.dpd.de/parcelstatus?query=';

    const HANDLING_FEE_OPTION = 'handling_fee';

    /** @var  DPDTransportProvider */
    protected $transportProvider;

    /** @var Channel */
    protected $channel;

    /** @var  DPDRequestFactory */
    protected $dpdRequestFactory;

    /** @var LocalizationHelper */
    protected $localizationHelper;

    /** @var  ShippingMethodTypeInterface[] */
    protected $types;

    /** @var  PackageProvider */
    protected $packageProvider;

    /** @var FileManager */
    protected $fileManager;

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * Construct
     * @param DPDTransportProvider $transportProvider
     * @param Channel $channel
     * @param DPDRequestFactory $dpdRequestFactory
     * @param LocalizationHelper $localizationHelper
     * @param PackageProvider $packageProvider
     * @param FileManager $fileManager
     * @param ManagerRegistry $doctrine
     */
    public function __construct(
        DPDTransportProvider $transportProvider,
        Channel $channel,
        DPDRequestFactory $dpdRequestFactory,
        LocalizationHelper $localizationHelper,
        PackageProvider $packageProvider,
        FileManager $fileManager,
        ManagerRegistry $doctrine
    ) {
        $this->transportProvider = $transportProvider;
        $this->channel = $channel;
        $this->dpdRequestFactory = $dpdRequestFactory;
        $this->localizationHelper = $localizationHelper;
        $this->packageProvider = $packageProvider;
        $this->fileManager = $fileManager;
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return static::IDENTIFIER . '_' . $this->channel->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function isGrouped()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        /** @var DPDTransport $transport */
        $transport = $this->channel->getTransport();
        return (string)$this->localizationHelper->getLocalizedValue($transport->getLabels());
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes()
    {
        if (!$this->types) {
            $this->types = [];

            /** @var DPDTransport $transport */
            $transport = $this->channel->getTransport();
            /** @var ShippingService $shippingServicesCodes */
            $shippingServices = $transport->getApplicableShippingServices();
            foreach ($shippingServices as $shippingService) {
                $this->types[] = new DPDShippingMethodType(
                    $this->getIdentifier(),
                    $transport,
                    $this->transportProvider,
                    $shippingService,
                    $this->packageProvider,
                    $this->dpdRequestFactory,
                    $this->fileManager,
                    $this->doctrine
                );
            }
        }

        return $this->types;
    }

    /**
     * @param string $identifier
     * @return DPDShippingMethodType|null
     */
    public function getType($identifier)
    {
        $methodTypes = $this->getTypes();
        foreach ($methodTypes as $methodType) {
            if ($methodType->getIdentifier() === (string)$identifier) {
                return $methodType;
            }
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfigurationFormType()
    {
        return DPDShippingMethodOptionsType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrder()
    {
        return 20;
    }

    public function getTrackingLink($number)
    {
        //FIXME: get current locale to localize url
        return self::TRACKING_URL . $number;
    }

    /**
     * {@inheritdoc}
     */
    public function calculatePrices(ShippingContextInterface $context, array $methodOptions, array $optionsByTypes)
    {
        $prices = [];
        foreach ($this->getTypes() as $type) {
            $typeId = $type->getIdentifier();
            $prices[$typeId] = $type->calculatePrice($context, $methodOptions, $optionsByTypes[$typeId]);
        }
        return $prices;
    }

    /**
     * @param Order $order
     * @return null|SetOrderResponse
     */
    public function setOrder(Order $order)
    {
        $methodType = $this->getType($order->getShippingMethodType());
        if ($methodType) {
            return $methodType->setOrder($order);
        }
        return null;
    }
}
