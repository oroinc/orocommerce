<?php

namespace Oro\Bundle\DPDBundle\Method;

use Oro\Bundle\DPDBundle\Cache\ZipCodeRulesCache;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Factory\DPDRequestFactory;
use Oro\Bundle\DPDBundle\Model\SetOrderRequest;
use Oro\Bundle\DPDBundle\Model\SetOrderResponse;
use Oro\Bundle\DPDBundle\Model\ZipCodeRulesResponse;
use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDSettings;
use Oro\Bundle\DPDBundle\Provider\DPDTransport as DPDTransportProvider;

class DPDHandler implements DPDHandlerInterface
{
    /** @var string */
    protected $identifier;

    /**
     * @var string
     */
    protected $methodId;

    /**
     * @var DPDSettings
     */
    protected $transport;

    /**
     * @var DPDTransportProvider
     */
    protected $transportProvider;

    /**
     * @var ShippingService
     */
    protected $shippingService;

    /**
     * @var PackageProvider
     */
    protected $packageProvider;

    /**
     * @var DPDRequestFactory
     */
    protected $dpdRequestFactory;

    /**
     * @var ZipCodeRulesCache
     */
    protected $zipCodeRulesCache;

    /**
     * @var OrderShippingLineItemConverterInterface
     */
    protected $shippingLineItemConverter;

    /** @var \DateTime */
    protected $today;

    /**
     * @param $identifier
     * @param string                                  $methodId
     * @param ShippingService                         $shippingService
     * @param DPDSettings                             $transport
     * @param DPDTransportProvider                    $transportProvider
     * @param PackageProvider                         $packageProvider
     * @param DPDRequestFactory                       $dpdRequestFactory
     * @param ZipCodeRulesCache                       $zipCodeRulesCache
     * @param OrderShippingLineItemConverterInterface $shippingLineItemConverter
     * @param \DateTime                               $today
     */
    public function __construct(
        $identifier,
        $methodId,
        ShippingService $shippingService,
        DPDSettings $transport,
        DPDTransportProvider $transportProvider,
        PackageProvider $packageProvider,
        DPDRequestFactory $dpdRequestFactory,
        ZipCodeRulesCache $zipCodeRulesCache,
        OrderShippingLineItemConverterInterface $shippingLineItemConverter,
        \DateTime $today = null
    ) {
        $this->identifier = $identifier;
        $this->methodId = $methodId;
        $this->shippingService = $shippingService;
        $this->transport = $transport;
        $this->transportProvider = $transportProvider;
        $this->packageProvider = $packageProvider;
        $this->dpdRequestFactory = $dpdRequestFactory;
        $this->zipCodeRulesCache = $zipCodeRulesCache;
        $this->shippingLineItemConverter = $shippingLineItemConverter;
        $this->today = $today;
        if (null === $this->today) {
            $this->today = new \DateTime('today');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param Order          $order
     * @param \DateTime|null $shipDate
     *
     * @return null|SetOrderResponse
     */
    public function shipOrder(Order $order, \DateTime $shipDate = null)
    {
        $convertedLineItems = $this->shippingLineItemConverter->convertLineItems($order->getLineItems());
        $packageList = $this->packageProvider->createPackages($convertedLineItems);
        if (!$packageList || count($packageList) !== 1) { //TODO: implement multi package support
            return null;
        }

        $setOrderRequest = $this->dpdRequestFactory->createSetOrderRequest(
            $this->transport,
            $this->shippingService,
            SetOrderRequest::START_ORDER_ACTION,
            $shipDate,
            $order->getId(),
            $order->getShippingAddress(),
            $order->getEmail(),
            $packageList
        );

        try {
            $setOrderResponse =
                $this->transportProvider->getSetOrderResponse($setOrderRequest, $this->transport);
        } catch (\InvalidArgumentException $e) {
            return null;
        }

        return $setOrderResponse;
    }

    /**
     * @param \DateTime|null $shipDate
     *
     * @return \DateTime
     */
    public function getNextPickupDay(\DateTime $shipDate = null)
    {
        while (($addHint = $this->checkShipDate($shipDate)) !== 0) {
            $shipDate->add(new \DateInterval('P'.$addHint.'D'));
        }

        return $shipDate;
    }

    /**
     * Check if shipDate is a valid pickup day.
     *
     * @param \DateTime $shipDate
     *
     * @return int 0 if shipDate is valid pickup day or a number of days to increase shipDate for a possible valid date
     */
    private function checkShipDate(\DateTime $shipDate)
    {
        $zipCodeRulesResponse = $this->fetchZipCodeRules();

        $shipDateMidnight = clone $shipDate;
        $shipDateMidnight->setTime(0, 0, 0);
        $diff = $this->today->diff($shipDateMidnight);
        $diffDays = (int) $diff->format('%R%a');
        if ($diffDays === 0) {
            $cutOffDate = clone $this->today;
            list($cutOffHour, $cutOffMin) =
                explode(
                    ':',
                    $this->shippingService->isClassicService() ?
                        $zipCodeRulesResponse->getClassicCutOff() :
                        $zipCodeRulesResponse->getExpressCutOff()
                );
            $cutOffDate->setTime((int) $cutOffHour, $cutOffMin);
            if ($shipDate > $cutOffDate) {
                return 1;
            }
        }

        // check if shipDate is saturday or sunday
        $shipDateWeekDay = (int) $shipDate->format('N');
        switch ($shipDateWeekDay) {
            case 6://saturday
                return 2;
            case 7://sunday
                return 1;
        }

        // check if shipDate inside noPickupDays
        if ($zipCodeRulesResponse->isNoPickupDay($shipDate)) {
            return 1;
        }

        return 0;
    }

    /**
     * @return ZipCodeRulesResponse
     */
    public function fetchZipCodeRules()
    {
        $zipCodeRulesRequest = $this->dpdRequestFactory->createZipCodeRulesRequest();
        $cacheKey = $this->zipCodeRulesCache->createKey($this->transport, $zipCodeRulesRequest, $this->methodId);

        if ($this->zipCodeRulesCache->containsZipCodeRules($cacheKey)) {
            return $this->zipCodeRulesCache->fetchZipCodeRules($cacheKey);
        }

        $zipCodeRulesResponse = $this->transportProvider->getZipCodeRulesResponse($this->transport);
        if ($zipCodeRulesResponse) {
            $this->zipCodeRulesCache->saveZipCodeRules($cacheKey, $zipCodeRulesResponse);
        }

        return $zipCodeRulesResponse;
    }
}
