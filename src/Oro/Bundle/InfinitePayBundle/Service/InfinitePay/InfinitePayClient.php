<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

use BeSimple\SoapClient\SoapClient;
use BeSimple\SoapClient\WsSecurityFilter;
use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\Logger\InfinitePayAPILoggerInterface;

class InfinitePayClient extends SoapClient implements InfinitePayClientInterface
{
    const TEST_WSDL = 'https://test.infinitepay.de/ws/InfinitePayAPI?wsdl';
    const LIVE_WSDL = 'https://app.infinitepay.de/ws/InfinitePayAPI?wsdl';

    /** @var InfinitePayConfigInterface */
    protected $config;

    /** @var InfinitePayAPILoggerInterface */
    protected $logger;

    /**
     * @var array The defined classes
     */
    private static $classmap = [
        'cancelOrder' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\cancelOrder',
        'REQUEST_CANCEL' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\RequestCancel',
        'genericRequest' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\GenericRequest',
        'clientData' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\ClientData',
        'orderTotal' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\OrderTotal',
        'cancelOrderResponse' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\CancelOrderResponse',
        'RESPONSE_CANCEL' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\ResponseCancel',
        'genericResponse' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\GenericResponse',
        'errorDataList' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\ErrorDataList',
        'errorData' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\ErrorData',
        'responseData' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\ResponseData',
        'GenericSoapFault' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\GenericSoapFault',
        'checkStatusOrder' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\CheckStatusOrder',
        'REQUEST_CHECK_STATUS' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\RequestCheckStatus',
        'checkStatusOrderResponse' =>
            'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\CheckStatusOrderResponse',
        'RESPONSE_CHECK_STATUS' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\RESPONSE_CHECK_STATUS',
        'modifyReservedOrder' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\ModifyReservedOrder',
        'REQUEST_MODIFY' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\RequestModify',
        'orderArticleList' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\OrderArticleList',
        'orderArticle' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\OrderArticle',
        'modifyReservedOrderResponse' =>
            'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\ModifyReservedOrderResponse',
        'RESPONSE_MODIFY' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\ResponseModify',
        'reserveOrder' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\ReserveOrder',
        'REQUEST_RESERVATION' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\RequestReservation',
        'bankData' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\BankData',
        'debtorData' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\DebtorData',
        'companyData' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\CompanyData',
        'invoiceData' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\InvoiceData',
        'shippingData' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\ShippingData',
        'reserveOrderResponse' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\ReserveOrderResponse',
        'RESPONSE_RESERVATION' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\ResponseReservation',
        'debtorCorrectedData' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\DebtorCorrectedData',
        'applyTransactionOnActivatedOrder' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\ApplyTransaction',
        'REQUEST_APPLY_TRANSACTION' =>
            'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\RequestApplyTransaction',
        'transactionData' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\transactionData',
        'applyTransactionOnActivatedOrderResponse' =>
            'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\ApplyTransactionResponse',
        'RESPONSE_APPLY_TRANSACTION' =>
            'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\ResponseApplyTransaction',
        'captureOrder' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\CaptureOrder',
        'REQUEST_CAPTURE' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\RequestCapture',
        'captureOrderResponse' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\CaptureOrderResponse',
        'RESPONSE_CAPTURE' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\ResponseCapture',
        'activateOrder' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\ActivateOrder',
        'REQUEST_ACTIVATION' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\RequestActivation',
        'activateOrderResponse' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\ActivateOrderResponse',
        'RESPONSE_ACTIVATION' => 'Oro\\Bundle\\InfinitePayBundle\\Service\\InfinitePay\\ResponseActivation',
    ];

    /**
     * @param InfinitePayConfigInterface    $config
     * @param InfinitePayAPILoggerInterface $logger
     * @param array                         $options
     */
    public function __construct(
        InfinitePayConfigInterface $config,
        InfinitePayAPILoggerInterface $logger,
        array $options = []
    ) {
        $this->config = $config;
        $this->logger = $logger;

        $options = $this->populateClassMap($options);
        $options = $this->setOptions($options);

        parent::__construct(
            $this->config->isTestModeEnabled()?static::TEST_WSDL:static::LIVE_WSDL,
            $options
        );

        $this->setWsseHeader();
    }

    /**
     * @param ReserveOrder $parameters
     *
     * @return reserveOrderResponse
     */
    public function reserveOrder(ReserveOrder $parameters)
    {
        /** @var reserveOrderResponse $response */
        $response = $this->__soapCall('reserveOrder', [$parameters]);

        if ($response === null || $this->hasErrorsInDebugMode($response)) {
            $this->logger->logApiError($this->__getLastRequest(), $this->__getLastResponse());
        }

        return $response;
    }

    /**
     * @param CaptureOrder $parameters
     *
     * @return CaptureOrderResponse
     */
    public function callCaptureOrder(CaptureOrder $parameters)
    {
        $response = $this->__soapCall('captureOrder', [$parameters]);

        if ($response === null || $this->hasErrorsInDebugMode($response)) {
            $this->logger->logApiError($this->__getLastRequest(), $this->__getLastResponse());
        }

        return $response;
    }

    /**
     * @param ActivateOrder $parameters
     *
     * @return ActivateOrderResponse
     */
    public function activateOrder(ActivateOrder $parameters)
    {
        $response = $this->__soapCall('activateOrder', [$parameters]);

        if ($response === null || $this->hasErrorsInDebugMode($response)) {
            $this->logger->logApiError($this->__getLastRequest(), $this->__getLastResponse());
        }

        return $response;
    }

    /**
     * @param ApplyTransaction $parameters
     *
     * @return ApplyTransactionResponse
     */
    public function applyTransactionOnActivatedOrder(ApplyTransaction $parameters)
    {
        return $this->__soapCall('applyTransactionOnActivatedOrder', [$parameters]);
    }

    /**
     * @param CancelOrder $parameters
     *
     * @return CancelOrderResponse
     */
    public function cancelOrder(CancelOrder $parameters)
    {
        return $this->__soapCall('cancelOrder', [$parameters]);
    }

    /**
     * @param ModifyReservedOrder $parameters
     *
     * @return ModifyReservedOrderResponse
     */
    public function modifyReservedOrder(ModifyReservedOrder $parameters)
    {
        return $this->__soapCall('modifyReservedOrder', [$parameters]);
    }

    /**
     * @param CheckStatusOrder $parameters
     *
     * @return CheckStatusOrderResponse
     */
    public function checkStatusOrder(CheckStatusOrder $parameters)
    {
        return $this->__soapCall('checkStatusOrder', [$parameters]);
    }

    /**
     * @param array $options
     *
     * @return array
     */
    private function populateClassMap(array $options)
    {
        foreach (self::$classmap as $key => $value) {
            if (!isset($options['classmap'][$key])) {
                $options['classmap'][$key] = $value;
            }
        }

        return $options;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    private function setOptions(array $options)
    {
        $options = array_merge([
            'features' => 1,
            'trace' => $this->config->isDebugModeEnabled(),
        ], $options);

        return $options;
    }

    private function setWsseHeader()
    {
        $wssFilter = new WsSecurityFilter(true, 600);
        $wssFilter->addUserData(
            $this->config->getUsername(),
            $this->config->getPassword(),
            WsSecurityFilter::PASSWORD_TYPE_TEXT
        );

        $soapKernel = $this->getSoapKernel();
        $soapKernel->registerFilter($wssFilter);
    }

    /**
     * @param GenericResponseInterface $response
     *
     * @return bool
     */
    private function responseHasErrors(GenericResponseInterface $response)
    {
        return $response === null || $response->getErrorData() !== null;
    }

    /**
     * @param ResponseBodyInterface $response
     *
     * @return bool
     */
    private function hasErrorsInDebugMode(ResponseBodyInterface $response)
    {
        return $this->config->isDebugModeEnabled() && $this->responseHasErrors($response->getResponse());
    }
}
