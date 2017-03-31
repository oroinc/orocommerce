<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option\OptionInterface;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Response\Response;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfig;

class AuthorizeNetSDKClient implements ClientInterface
{
    /**
     * {@inheritdoc}
     */
    public function send(OptionInterface $option)
    {
        $request = (new AnetAPI\CreateTransactionRequest());
        $request->setMerchantAuthentication((new AnetAPI\MerchantAuthenticationType())
            ->setName($option->getConfig()->getApiLogin())
            ->setTransactionKey($option->getConfig()->getTransactionKey()));

        $request->setTransactionRequest((new AnetAPI\TransactionRequestType())
            ->setTransactionType($option->getTransactionType())
            ->setAmount($option->getAmount())
            ->setPayment((new AnetAPI\PaymentType())
                ->setOpaqueData((new AnetAPI\OpaqueDataType())
                    ->setDataDescriptor($option->getDataDescriptor())
                    ->setDataValue($option->getDataValue()))));

        $controller = new AnetController\CreateTransactionController($request);
        $apiResponse = $controller
            ->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

        return new Response($apiResponse);
    }
}
