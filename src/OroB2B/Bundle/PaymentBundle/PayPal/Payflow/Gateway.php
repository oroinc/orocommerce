<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Client\ClientInterface;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request\RequestInterface;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\ResponseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Gateway
{
    /** @var ValidatorInterface */
    protected $validator;

    /** @var ClientInterface */
    protected $client;

    /**
     * @param ClientInterface $client
     * @param ValidatorInterface $validator
     */
    public function __construct(ClientInterface $client, ValidatorInterface $validator)
    {
        $this->client = $client;
        $this->validator = $validator;

        $this->resolver = new OptionsResolver();
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function request(RequestInterface $request)
    {
        $violations = $this->validator->validate($request);

        if ($violations->count()) {
            throw new InvalidOptionsException((string)$violations, $request->getOptions());
        }

        $response = $this->client->send($request->getOptions());

        $violations = $this->validator->validate($response);

        if ($violations->count()) {
            throw new InvalidOptionsException((string)$violations, $response->getData());
        }

        return $response;
    }
}
