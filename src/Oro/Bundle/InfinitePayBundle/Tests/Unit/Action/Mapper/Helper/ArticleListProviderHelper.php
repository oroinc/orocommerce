<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Mapper\Helper;

use Oro\Bundle\InfinitePayBundle\Action\Provider\ArticleListProviderInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderArticle;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderArticleList;

/**
 * {@inheritdoc}
 */
class ArticleListProviderHelper extends \PHPUnit_Framework_TestCase
{
    /** @var OrderArticleStub[] */
    protected $articles;

    /**
     * @param array $articles
     *
     * @return ArticleListProviderInterface
     */
    public function getArticleListProvider(array $articles = [])
    {
        if (empty($articles)) {
            $this->articles = $this->getMockArticles();
        }
        $articleListProvider = $this
            ->getMockBuilder('Oro\Bundle\InfinitePayBundle\Action\Provider\ArticleListProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $articleList = new OrderArticleList();
        $articleList->setARTICLE($this->getArticles())
        ;

        return $articleListProvider;
    }

    private function getArticles()
    {
        $articles = [];
        /** @var OrderArticleStub $articleData */
        foreach ($this->articles as $articleData) {
            $orderArticle = (new OrderArticle())
                ->setArticleId($articleData->getId())
                ->setArticleName($articleData->getName())
                ->setArticlePriceGross($articleData->getPriceGross())
                ->setArticlePriceNet($articleData->getPriceNet())
                ->setArticleQuantity($articleData->getQuantity())
                ->setArticleVatPerc($articleData->getVatPercentage())
                ;
            $articles[] = $orderArticle;
        }

        return $articles;
    }

    private function getMockArticles()
    {
        $articleData = [
            [
                'id' => 'id_1',
                'name' => 'prod_1',
                'price_gross' => 1190,
                'price_net' => 1000,
                'vat_percentage' => 19.0,
                'quantity' => 1,
            ],
            [
                'id' => 'id_2',
                'name' => 'prod_2',
                'price_gross' => 1070,
                'price_net' => 1000,
                'vat_percentage' => 7.0,
                'quantity' => 2,
            ],
            [
                'id' => 'id_3',
                'name' => 'prod_1',
                'price_gross' => 1795,
                'price_net' => 1500,
                'vat_percentage' => 19.0,
                'quantity' => 1,
            ],
        ];
        $articleArray = [];
        foreach ($articleData as $data) {
            $articleArray[] = new OrderArticleStub($data);
        }

        return $articleArray;
    }
}
