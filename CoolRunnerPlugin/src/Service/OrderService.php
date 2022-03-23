<?php

namespace CoolRunnerPlugin\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class OrderService
{
    private EntityRepositoryInterface $orderRepository;

    public function __construct(EntityRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function readData(Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', 'af41d3a3019147fb8edeabfe99a729b5'));
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('addresses');
        $criteria->addAssociation('customFields');
        $criteria->addAssociation('lineItems.product.customFields');
        $criteria->addAssociation('deliveries.shippingMethod.customFields');

        return $this->orderRepository->search($criteria, $context)->first();
    }

}