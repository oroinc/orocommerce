<?php

namespace Oro\Bundle\DPDBundle\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DPDBundle\Cache\ZipCodeRulesCache;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Factory\DPDRequestFactory;
use Oro\Bundle\DPDBundle\Form\Type\DPDShippingMethodOptionsType;
use Oro\Bundle\DPDBundle\Model\SetOrderRequest;
use Oro\Bundle\DPDBundle\Model\ZipCodeRulesResponse;
use Oro\Bundle\DPDBundle\Model\SetOrderResponse;
use Oro\Bundle\DPDBundle\Provider\DPDTransport as DPDTransportProvider;
use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\DPDBundle\Provider\RateTablePriceProvider;
use Oro\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

class DPDShippingMethodType implements ShippingMethodTypeInterface
{
    /**
     * @var string
     */
    protected $methodId;

    /**
     * @var DPDTransport
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
     * @var RateTablePriceProvider
     */
    protected $rateTablePriceProvider;

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

    /**
     * @param string                                  $methodId
     * @param DPDTransport                            $transport
     * @param DPDTransportProvider                    $transportProvider
     * @param ShippingService                         $shippingService
     * @param PackageProvider                         $packageProvider
     * @param RateTablePriceProvider                  $rateTablePriceProvider
     * @param DPDRequestFactory                       $dpdRequestFactory
     * @param ZipCodeRulesCache                       $zipCodeRulesCache
     * @param OrderShippingLineItemConverterInterface $shippingLineItemConverter
     */
    public function __construct(
        $methodId,
        DPDTransport $transport,
        DPDTransportProvider $transportProvider,
        ShippingService $shippingService,
        PackageProvider $packageProvider,
        RateTablePriceProvider $rateTablePriceProvider,
        DPDRequestFactory $dpdRequestFactory,
        ZipCodeRulesCache $zipCodeRulesCache,
        OrderShippingLineItemConverterInterface $shippingLineItemConverter
    ) {
        $this->methodId = $methodId;
        $this->transport = $transport;
        $this->transportProvider = $transportProvider;
        $this->shippingService = $shippingService;
        $this->packageProvider = $packageProvider;
        $this->rateTablePriceProvider = $rateTablePriceProvider;
        $this->dpdRequestFactory = $dpdRequestFactory;
        $this->zipCodeRulesCache = $zipCodeRulesCache;
        $this->shippingLineItemConverter = $shippingLineItemConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->shippingService->getCode();
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->shippingService->getDescription();
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrder()
    {
        return 0;
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
    public function calculatePrice(ShippingContextInterface $context, array $methodOptions, array $typeOptions)
    {
        $packageList = $this->packageProvider->createPackages($context->getLineItems());
        if (!$packageList || count($packageList) !== 1) { //TODO: implement multi package support
            return null;
        }

        $price = null;
        if ($this->transport->getRatePolicy() === DPDTransport::FLAT_RATE_POLICY) {
            $price = $this->transport->getFlatRatePriceValue();
        } elseif ($this->transport->getRatePolicy() === DPDTransport::TABLE_RATE_POLICY) {
            $rate = $this->rateTablePriceProvider->getRateByServiceAndDestination(
                $this->transport,
                $this->shippingService,
                $context->getShippingAddress()
            );
            if ($rate !== null) {
                $price = $rate->getPriceValue();
            }
        }

        if ($price === null) {
            return null;
        }

        $optionsDefaults = [
            DPDShippingMethod::HANDLING_FEE_OPTION => 0,
        ];
        $methodOptions = array_merge($optionsDefaults, $methodOptions);
        $typeOptions = array_merge($optionsDefaults, $typeOptions);

        $handlingFee = $methodOptions[DPDShippingMethod::HANDLING_FEE_OPTION] + $typeOptions[DPDShippingMethod::HANDLING_FEE_OPTION];

        return Price::create((float) $price + (float) $handlingFee, $context->getCurrency());
    }

    /**
     * @param Order     $order
     * @param \DateTime $shipDate
     *
     * @return null|SetOrderResponse
     */
    public function shipOrder(Order $order, \DateTime $shipDate)
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

        $setOrderResponse = $this->transportProvider->getSetOrderResponse($setOrderRequest, $this->transport);

        return $setOrderResponse;
    }

    /**
     * @param \DateTime $shipDate
     *
     * @return bool
     */
    public function isShipDatePickupDay(\DateTime $shipDate)
    {
        return $this->checkShipDate($shipDate) === 0;
    }

    /**
     * @param \DateTime $shipDate
     *
     * @return \DateTime
     */
    public function getNextPickupDay(\DateTime $shipDate)
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
    public function checkShipDate(\DateTime $shipDate)
    {
        $zipCodeRulesResponse = $this->fetchZipCodeRules();

        // check if cut-off shipping date is today
        $today = new \DateTime('today');
        $shipDateMidnight = clone $shipDate;
        $shipDateMidnight->setTime(0, 0, 0);
        $diff = $today->diff($shipDateMidnight);
        $diffDays = (int) $diff->format('%R%a');
        if ($diffDays === 0) {
            $cutOffDate = \DateTime::createFromFormat(
                'H:i',
                $this->shippingService->isClassicService() ?
                    $zipCodeRulesResponse->getClassicCutOff() :
                    $zipCodeRulesResponse->getExpressCutOff()
            );

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
        $cacheKey = $this->zipCodeRulesCache->createKey($this->transport, $zipCodeRulesRequest,
            $this->getIdentifier());

        if ($this->zipCodeRulesCache->containsZipCodeRules($cacheKey)) {
            return $this->zipCodeRulesCache->fetchZipCodeRules($cacheKey);
        }

        $zipCodeRulesResponse = $this->transportProvider->getZipCodeRulesResponse(
            $zipCodeRulesRequest,
            $this->transport
        );
        if ($zipCodeRulesResponse) {
            $this->zipCodeRulesCache->saveZipCodeRules($cacheKey, $zipCodeRulesResponse);
        }

        return $zipCodeRulesResponse;
    }
}
