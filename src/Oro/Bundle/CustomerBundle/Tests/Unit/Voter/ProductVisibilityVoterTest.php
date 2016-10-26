<?php
namespace Oro\Bundle\CustomerBundle\Tests\Unit\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebsiteSearchBundle\Query\Result\Item;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Component\Testing\Unit\EntityTrait;

use Oro\Bundle\CustomerBundle\Acl\Voter\ProductVisibilityVoter;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Search\ProductRepository;


class ProductVisibilityVoterTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;
    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var TokenInterface
     */
    protected $currentToken;

    protected $doctrineHelper;
    protected $container;
    protected $item;

    public function setUp()
    {
        $fakeRequestStack = new RequestStack();
        $fakeRequest = new Request();
        $fakeRequestStack->push($fakeRequest);

        $this->container = $this->getMockBuilder(Container::class)->setMethods(['get', 'getParameter'])->getMock();

        $this->container->method('get')->willReturn($fakeRequestStack);
        $this->container->method('getParameter')->willReturn(true);

        $this->frontendHelper = new FrontendHelper('admin', $this->container );
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()->getMock();
        $this->productRepository = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(array('searchFilteredBySkus'))->getMock();
        $this->item = $this->getMock(Item::class);
        $this->productRepository->method('findOne')->will(
            $this->returnValueMap(
                array(
                    array('NOT-FOUND', 'FOUND'),
                    array(null, $this->item)
                )
            )
        );
        $this->currentToken = $this->getMock(TokenInterface::class);
    }

    public function testVote()
    {
        $voter = new ProductVisibilityVoter($this->doctrineHelper);
        $voter->setFrontendHelper($this->frontendHelper);
        $voter->setProductSearchRepository($this->productRepository);
        $object = null;
        $attributes = array();
        $vote = $voter->vote($this->currentToken, $object, $attributes);

        $this->assertEquals($vote, ProductVisibilityVoter::ACCESS_ABSTAIN);

        $object = $this->item;
        $attributes = array();
        $vote = $voter->vote($this->currentToken, $object, $attributes);
        $this->assertEquals($vote, ProductVisibilityVoter::ACCESS_ABSTAIN);



    }
    public function testGetPermissionForAttribute()
    {


        $voter = new ProductVisibilityVoter($this->doctrineHelper);
        $voter->setFrontendHelper($this->frontendHelper);
        $voter->setProductSearchRepository($this->productRepository);
    }
}