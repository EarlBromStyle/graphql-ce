<?php
declare(strict_types=1);
/**
 * WishlistItemTypeResolver
 *
 * @copyright Copyright © 2018 brandung GmbH & Co. KG. All rights reserved.
 * @author    david.verholen@brandung.de
 */

namespace Magento\WishlistGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Wishlist\Model\Item;
use Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory as WishlistItemCollectionFactory;

class WishlistItemsResolver implements ResolverInterface
{

    /**
     * @var WishlistItemCollectionFactory
     */
    private $wishlistItemCollectionFactory;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        WishlistItemCollectionFactory $wishlistItemCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->wishlistItemCollectionFactory = $wishlistItemCollectionFactory;
        $this->storeManager = $storeManager;
    }


    /**
     * Fetches the data from persistence models and format it according to the GraphQL schema.
     *
     * @param \Magento\Framework\GraphQl\Config\Element\Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @throws \Exception
     * @return mixed|Value
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $storeIds = array_map(function (StoreInterface $store) {
            return $store->getId();
        }, $this->storeManager->getStores());
        $wishlistItemCollection = $this->wishlistItemCollectionFactory->create();
        $wishlistItemCollection->addCustomerIdFilter($context->getUserId());
        $wishlistItemCollection->addStoreFilter($storeIds);
        $wishlistItemCollection->setVisibilityFilter();
        $wishlistItemCollection->load();
        return array_map(function (Item $wishlistItem) {
            return [
                'id' => $wishlistItem->getId(),
                'qty' => $wishlistItem->getData('qty'),
                'description' => (string)$wishlistItem->getDescription(),
                'added_at' => $wishlistItem->getAddedAt(),
                'product' => array_merge($wishlistItem->getProduct()->toArray(), ['model' => $wishlistItem->getProduct()])
            ];
        }, $wishlistItemCollection->getItems());
    }
}
