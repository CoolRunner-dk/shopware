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
        $criteria->addFilter(new EqualsFilter('id', 'eeca564079454a279b65b5084a94bf66'));
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('addresses');
        $criteria->addAssociation('customFields');
        $criteria->addAssociation('lineItems.product.customFields');

        return $this->orderRepository->search($criteria, $context)->first();
    }

}