<?php

namespace CoolRunnerPlugin\Subscriber;

use CoolRunnerPlugin\Controller\CoolRunnerAPI;
use CoolRunnerPlugin\Service\OrderService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\StateMachine\Event\StateMachineStateChangeEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderStateSubscriber implements EventSubscriberInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var SystemConfigService */
    private $systemConfigService;

    /** @var CoolRunnerAPI */
    private $apiClient;

    /** @var OrderService */
    private $orderService;

    /** @var EntityRepositoryInterface */
    private $countryRepository;

    public function __construct(SystemConfigService $systemConfigService, LoggerInterface $logger, OrderService $orderService, EntityRepositoryInterface $countryRepository)
    {
        $this->logger = $logger;
        $this->systemConfigService = $systemConfigService;
        $this->apiClient = new CoolRunnerAPI($systemConfigService);
        $this->orderService = $orderService;
        $this->countryRepository = $countryRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            "state_machine.order_delivery.state_changed" => "onDeliveryStateChange"
        ];
    }

    public function onDeliveryStateChange(StateMachineStateChangeEvent $event)
    {
//        $this->logger->debug('CoolRunnerTest: ' . json_encode($event->getContext()));

        if($event->getStateEventName() == 'state_enter.order_delivery.state.shipped') {
            // Get order
            /** @var OrderEntity $order */
            $order = $this->orderService->readData($event->getContext());

            // Get country
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('id', $order->addresses->first()->countryId));

            $country = $this->countryRepository->search($criteria, $event->getContext())->first();

            // Create shipment
            $this->logger->debug('CoolRunnerTest: ' . $this->apiClient->createShipment($order, $country));
        }
    }
}