<?php

namespace Oro\Bundle\PaymentBundle\Event;

use Symfony\Component\HttpFoundation\Response;

/**
 * Abstract Payment callback Event
 */
abstract class AbstractCallbackEvent extends AbstractTransactionEvent
{
    /** @var array */
    protected $data;

    /** @var Response */
    protected $response;

    public function __construct(array $data = [])
    {
        $this->data = $data;

        $this->response = new Response(null, Response::HTTP_FORBIDDEN);
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return mixed
     */
    abstract public function getEventName();

    public function markSuccessful()
    {
        $this->response->setStatusCode(Response::HTTP_OK);
    }

    public function markFailed()
    {
        $this->response->setStatusCode(Response::HTTP_FORBIDDEN);

        $this->stopPropagation();
    }
}
