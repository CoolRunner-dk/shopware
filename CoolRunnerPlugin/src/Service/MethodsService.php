<?php

namespace CoolRunnerPlugin\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class MethodsService
{
    /** @var EntityRepositoryInterface $methodsRepository */
    private EntityRepositoryInterface $methodsRepository;

    public function __construct(EntityRepositoryInterface $methodsRepository)
    {
        $this->methodsRepository = $methodsRepository;
    }

    public function writeData(Context $context, $name, $cps, $from, $to)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('cps', $cps));

        if(empty($this->methodsRepository->search($criteria, $context)->first())) {
            $this->methodsRepository->upsert([
                [
                    'name' =>  $name,
                    'cps' => $cps,
                    'from' => $from,
                    'to' => $to
                ]
            ], $context);
        }
    }
}