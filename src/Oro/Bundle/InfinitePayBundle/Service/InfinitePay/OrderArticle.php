<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class OrderArticle
{
    /**
     * @var string
     */
    protected $ARTICLE_DESCRIPTION;

    /**
     * @var string
     */
    protected $ARTICLE_ID;

    /**
     * @var string
     */
    protected $ARTICLE_NAME;

    /**
     * @var string
     */
    protected $ARTICLE_PRICE_GROSS;

    /**
     * @var string
     */
    protected $ARTICLE_PRICE_NET;

    /**
     * @var string
     */
    protected $ARTICLE_QUANTITY;

    /**
     * @var string
     */
    protected $ARTICLE_VAT_PERC;

    /**
     * @param string $ARTICLE_DESCRIPTION
     * @param string $ARTICLE_ID
     * @param string $ARTICLE_NAME
     * @param string $ARTICLE_PRICE_GROSS
     * @param string $ARTICLE_PRICE_NET
     * @param string $ARTICLE_QUANTITY
     */
    public function __construct(
        $ARTICLE_DESCRIPTION = null,
        $ARTICLE_ID = null,
        $ARTICLE_NAME = null,
        $ARTICLE_PRICE_GROSS = null,
        $ARTICLE_PRICE_NET = null,
        $ARTICLE_QUANTITY = null
    ) {
        $this->ARTICLE_DESCRIPTION = $ARTICLE_DESCRIPTION;
        $this->ARTICLE_ID = $ARTICLE_ID;
        $this->ARTICLE_NAME = $ARTICLE_NAME;
        $this->ARTICLE_PRICE_GROSS = $ARTICLE_PRICE_GROSS;
        $this->ARTICLE_PRICE_NET = $ARTICLE_PRICE_NET;
        $this->ARTICLE_QUANTITY = $ARTICLE_QUANTITY;
    }

    /**
     * @return string
     */
    public function getArticleDescription()
    {
        return $this->ARTICLE_DESCRIPTION;
    }

    /**
     * @param string $ARTICLE_DESCRIPTION
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderArticle
     */
    public function setArticleDescription($ARTICLE_DESCRIPTION)
    {
        $this->ARTICLE_DESCRIPTION = $ARTICLE_DESCRIPTION;

        return $this;
    }

    /**
     * @return string
     */
    public function getArticleId()
    {
        return $this->ARTICLE_ID;
    }

    /**
     * @param string $ARTICLE_ID
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderArticle
     */
    public function setArticleId($ARTICLE_ID)
    {
        $this->ARTICLE_ID = $ARTICLE_ID;

        return $this;
    }

    /**
     * @return string
     */
    public function getArticleName()
    {
        return $this->ARTICLE_NAME;
    }

    /**
     * @param string $ARTICLE_NAME
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderArticle
     */
    public function setArticleName($ARTICLE_NAME)
    {
        $this->ARTICLE_NAME = $ARTICLE_NAME;

        return $this;
    }

    /**
     * @return string
     */
    public function getArticlePriceGross()
    {
        return $this->ARTICLE_PRICE_GROSS;
    }

    /**
     * @param string $ARTICLE_PRICE_GROSS
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderArticle
     */
    public function setArticlePriceGross($ARTICLE_PRICE_GROSS)
    {
        $this->ARTICLE_PRICE_GROSS = $ARTICLE_PRICE_GROSS;

        return $this;
    }

    /**
     * @return string
     */
    public function getArticlePriceNet()
    {
        return $this->ARTICLE_PRICE_NET;
    }

    /**
     * @param string $ARTICLE_PRICE_NET
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderArticle
     */
    public function setArticlePriceNet($ARTICLE_PRICE_NET)
    {
        $this->ARTICLE_PRICE_NET = $ARTICLE_PRICE_NET;

        return $this;
    }

    /**
     * @return string
     */
    public function getArticleQuantity()
    {
        return $this->ARTICLE_QUANTITY;
    }

    /**
     * @param string $ARTICLE_QUANTITY
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderArticle
     */
    public function setArticleQuantity($ARTICLE_QUANTITY)
    {
        $this->ARTICLE_QUANTITY = $ARTICLE_QUANTITY;

        return $this;
    }

    /**
     * @return string
     */
    public function getArticleVatPerc()
    {
        return $this->ARTICLE_VAT_PERC;
    }

    /**
     * @param string $ARTICLE_VAT_PERC
     *
     * @return $this
     */
    public function setArticleVatPerc($ARTICLE_VAT_PERC)
    {
        $this->ARTICLE_VAT_PERC = $ARTICLE_VAT_PERC;

        return $this;
    }
}
