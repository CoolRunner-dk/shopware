<?php

namespace CoolRunnerPlugin\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class DeliveryService
{
    private EntityRepositoryInterface $deliveryRepository;

    public function __construct(EntityRepositoryInterface $deliveryRepository)
    {
        $this->deliveryRepository = $deliveryRepository;
    }

    public function readData(Context $context)
    {
        // TODO: Get id
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', '8016fe2995c04f5f8c8b6d082db0d57e'));

        return $this->deliveryRepository->search($criteria, $context)->first();
    }

    public function writeData(Context $context, $delivery_id, $package_number)
    {
        $this->deliveryRepository->update([
            [
                'id' => $delivery_id,
                'trackingCodes' => [$package_number]
            ]
        ], $context);
    }
}