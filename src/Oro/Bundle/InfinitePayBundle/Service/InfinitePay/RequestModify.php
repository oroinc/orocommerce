<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class RequestModify extends GenericRequest
{
    /**
     * @var ClientData
     */
    protected $CLIENT_DATA;

    /**
     * @var OrderArticleList
     */
    protected $ARTICLES;

    /**
     * @var OrderTotal
     */
    protected $ORDER_DATA;

    /**
     * @return ClientData
     */
    public function getClientData()
    {
        return $this->CLIENT_DATA;
    }

    /**
     * @param ClientData $CLIENT_DATA
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestModify
     */
    public function setClientData($CLIENT_DATA)
    {
        $this->CLIENT_DATA = $CLIENT_DATA;

        return $this;
    }

    /**
     * @return OrderArticleList
     */
    public function getArticles()
    {
        return $this->ARTICLES;
    }

    /**
     * @param OrderArticleList $ARTICLES
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestModify
     */
    public function setArticles($ARTICLES)
    {
        $this->ARTICLES = $ARTICLES;

        return $this;
    }

    /**
     * @return OrderTotal
     */
    public function getOrderData()
    {
        return $this->ORDER_DATA;
    }

    /**
     * @param OrderTotal $ORDER_DATA
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestModify
     */
    public function setOrderData($ORDER_DATA)
    {
        $this->ORDER_DATA = $ORDER_DATA;

        return $this;
    }
}
