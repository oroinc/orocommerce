<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Provider;

use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderArticle;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderArticleList;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\TaxBundle\Model\Result;

class ArticleListProvider implements ArticleListProviderInterface
{
    /**
     * @var InvoiceTotalsProviderInterface
     */
    protected $invoiceTotalsProvider;

    /**
     * ArticleListProvider constructor.
     *
     * @param InvoiceTotalsProviderInterface $invoiceTotalsProvider
     */
    public function __construct(InvoiceTotalsProviderInterface $invoiceTotalsProvider)
    {
        $this->invoiceTotalsProvider = $invoiceTotalsProvider;
    }

    /**
     * @param Order $order
     *
     * @return OrderArticleList
     */
    public function getArticleList(Order $order)
    {
        $tax = $this->invoiceTotalsProvider->getTax($order);
        $articles = $this->getArticlesArray($order, $tax);
        $orderArticleList = new OrderArticleList();
        $orderArticleList->setARTICLE($articles);

        return $orderArticleList;
    }

    /**
     * @param Order $order
     * @param $tax
     *
     * @return array
     */
    private function getArticlesArray(Order $order, $tax)
    {
        $taxPrices = $tax['data']['items'];
        $lineItems = $order->getLineItems()->toArray();

        return $this->populateArticlesWithTaxes($lineItems, $taxPrices);
    }

    /**
     * @param Result $result
     *
     * @return int
     */
    private function extractTax(Result $result)
    {
        foreach ($result->getTaxes() as $taxResultElement) {
            $rate = (float) $taxResultElement->getRate();

            return round($rate * 10000, 0, PHP_ROUND_HALF_UP);
        }

        return 0;
    }

    /**
     * @param array $lineItems
     * @param array $taxPrices
     *
     * @return array
     */
    private function populateArticlesWithTaxes(array $lineItems, array $taxPrices)
    {
        $articles = [];
        /**
         * @var int
         * @var OrderLineItem $item
         */
        foreach ($lineItems as $index => $item) {
            /** @var Result $result */
            $result = $taxPrices[$index];
            $article = new OrderArticle();

            $article->setArticleId($item->getProductSku());
            $article->setArticleName($item->getProduct()->getName()->getString());
            $article->setArticlePriceNet($this->convertToCentInt($item->getPrice()->getValue()));
            $article->setArticleQuantity($item->getQuantity());

            $article->setArticleVatPerc($this->extractTax($result));
            $article->setArticlePriceGross($this->convertToCentInt($result->getUnit()->getIncludingTax()));
            $articles[] = $article;
        }

        return $articles;
    }

    /**
     * @param float $input
     *
     * @return int
     */
    private function convertToCentInt($input)
    {
        return round($input * 100, 0, PHP_ROUND_HALF_UP);
    }
}
