<?php

namespace Oro\Bundle\DPDBundle\Method;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Factory\DPDRequestFactory;
use Oro\Bundle\DPDBundle\Model\GetZipCodeRulesResponse;
use Oro\Bundle\DPDBundle\Model\PriceTable;
use Oro\Bundle\DPDBundle\Model\SetOrderResponse;
use Oro\Bundle\DPDBundle\Provider\DPDTransport as DPDTransportProvider;
use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\DPDBundle\Form\Type\DPDShippingMethodTypeOptionsType;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Bundle\AttachmentBundle\Entity\File as AttachmentFile;

class DPDShippingMethodType implements ShippingMethodTypeInterface
{
    const FLAT_PRICE_OPTION = 'flat_price';
    const TABLE_PRICE_OPTION = 'table_price';

    const START_ORDER_ACTION = 'startOrder';
    const CHECK_ORDER_DATA_ACTION = 'checkOrderData';

    /** @var string */
    protected $methodId;

    /** @var DPDTransport */
    protected $transport;

    /** @var DPDTransportProvider */
    protected $transportProvider;

    /** @var ShippingService */
    protected $shippingService;

    /** @var  PackageProvider */
    protected $packageProvider;

    /** @var  DPDRequestFactory */
    protected $dpdRequestFactory;

    /** @var FileManager */
    protected $fileManager;

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param string $methodId
     * @param DPDTransport $transport
     * @param DPDTransportProvider $transportProvider
     * @param ShippingService $shippingService
     * @param PackageProvider $packageProvider
     * @param DPDRequestFactory $dpdRequestFactory
     * @param FileManager $fileManager
     * @param ManagerRegistry $doctrine
     */
    public function __construct(
        $methodId,
        DPDTransport $transport,
        DPDTransportProvider $transportProvider,
        ShippingService $shippingService,
        PackageProvider $packageProvider,
        DPDRequestFactory $dpdRequestFactory,
        FileManager $fileManager,
        ManagerRegistry $doctrine
    ) {
        $this->methodId = $methodId;
        $this->transport = $transport;
        $this->transportProvider = $transportProvider;
        $this->shippingService = $shippingService;
        $this->packageProvider = $packageProvider;
        $this->dpdRequestFactory = $dpdRequestFactory;
        $this->fileManager = $fileManager;
        $this->doctrine = $doctrine;
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
        return DPDShippingMethodTypeOptionsType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function calculatePrice(ShippingContextInterface $context, array $methodOptions, array $typeOptions)
    {
        $packageList = $this->packageProvider->createFromShippingContext($context);
        if (count($packageList) !== 1) { //TODO: implement multi package support
            return null;
        }

        $priceTable = new PriceTable();
        $priceTableItems = explode("\n", $typeOptions[static::TABLE_PRICE_OPTION]);
        $priceTable->fromArray($priceTableItems);

        $countryIso2 = $context->getShippingAddress()->getCountryIso2();

        $price = $priceTable->get($countryIso2, $typeOptions[static::FLAT_PRICE_OPTION]);

        $optionsDefaults = [
            DPDShippingMethod::HANDLING_FEE_OPTION => 0,
        ];
        $methodOptions = array_merge($optionsDefaults, $methodOptions);
        $typeOptions = array_merge($optionsDefaults, $typeOptions);

        $handlingFee = $methodOptions[DPDShippingMethod::HANDLING_FEE_OPTION] + $typeOptions[DPDShippingMethod::HANDLING_FEE_OPTION];

        return Price::create((float)$price + (float)$handlingFee, $context->getCurrency());
    }

    /**
     * @param Order $order
     * @return SetOrderResponse|null
     */
    public function setOrder(Order $order)
    {
        //get pickup days through getZipCodeRules request FIXME: cache this
        $getZipCodeRulesRequest = $this->dpdRequestFactory->createGetZipCodeRulesRequest();
        $getZipCodeRulesResponse = $this->transportProvider->getZipCodeRulesResponse(
            $getZipCodeRulesRequest,
            $this->transport
        );

        $shipDate = $this->checkShipDate(new \DateTime('now'), $getZipCodeRulesResponse);

        $setOrderRequest = $this->dpdRequestFactory->createSetOrderRequest(
            $this->transport,
            $order,
            static::START_ORDER_ACTION,
            $this->shippingService,
            $shipDate
        );

        $setOrderResponse = $this->transportProvider->setOrderResponse($setOrderRequest, $this->transport);

        if ($setOrderResponse && $setOrderResponse->isSuccessful()) {
            $this->linkLabelToOrder(
                $order,
                base64_decode($setOrderResponse->getLabelPDF()),
                $setOrderResponse->getParcelNumber() . '.pdf',
                $setOrderResponse->getParcelNumber()
            );

            $this->addTrackingNumberToOrder($order, $setOrderResponse->getParcelNumber());
        }

        return $setOrderResponse;
    }

    /**
     * Check if $shipDate is a valid ship date
     *
     * @param \DateTime $shipDate
     * @param GetZipCodeRulesResponse $getZipCodeRulesResponse
     * @return \DateTime
     */
    public function checkShipDate(\DateTime $shipDate, GetZipCodeRulesResponse $getZipCodeRulesResponse) {
        //check cutOff if shipdate is today
        $today = new \DateTime('today');
        $shipDateMidnight = clone $shipDate;
        $shipDateMidnight->setTime(0,0,0);
        $diff = $today->diff($shipDateMidnight);
        $diffDays = (integer)$diff->format( "%R%a" );
        if ($diffDays === 0) {
            $cutOffDate = \DateTime::createFromFormat(
                'H:i',
                $this->shippingService->isClassicService()?
                    $getZipCodeRulesResponse->getClassicCutOff():
                    $getZipCodeRulesResponse->getExpressCutOff()
            );

            if ($shipDate > $cutOffDate) {
                return $this->checkShipDate($shipDateMidnight->add(new \DateInterval('P1D')), $getZipCodeRulesResponse);
            }
        }

        //check if shipDate is saturday or sunday
        $shipDateWeekDay = (integer)$shipDate->format('N');
        switch ($shipDateWeekDay) {
            case 6://saturday
                return $this->checkShipDate($shipDateMidnight->add(new \DateInterval('P2D')), $getZipCodeRulesResponse);
            case 7://sunday
                return $this->checkShipDate($shipDateMidnight->add(new \DateInterval('P1D')), $getZipCodeRulesResponse);
        }

        //check if shipDate inside noPickupDays
        if ($getZipCodeRulesResponse->isNoPickupDay($shipDate)) {
            return $this->checkShipDate($shipDateMidnight->add(new \DateInterval('P1D')), $getZipCodeRulesResponse);
        }

        return $shipDate;
    }

    protected function linkLabelToOrder(Order $order, $labelContent, $labelFileName, $labelComment) {
        $tmpFile = $this->fileManager->writeToTemporaryFile($labelContent);
        $attachmentFile = new AttachmentFile();
        $attachmentFile->setFile($tmpFile);
        $attachmentFile->setOriginalFilename($labelFileName);

        $attachment = new Attachment();
        $attachment->setTarget($order);
        $attachment->setFile($attachmentFile);
        $attachment->setComment($labelComment);

        $em = $this->doctrine->getManagerForClass(Attachment::class);
        $em->persist($attachment);
        $em->flush();
    }

    protected function addTrackingNumberToOrder(Order $order, $trackingNumber) {
        $shippingTracking = new OrderShippingTracking();
        $shippingTracking->setMethod($order->getShippingMethod());
        $shippingTracking->setNumber($trackingNumber);
        $order->addShippingTracking($shippingTracking);

        $em = $this->doctrine->getManagerForClass(OrderShippingTracking::class);
        $em->persist($shippingTracking);
        $em->flush();
    }
}
