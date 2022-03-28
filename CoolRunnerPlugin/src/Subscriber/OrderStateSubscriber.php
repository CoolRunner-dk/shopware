<?php

namespace CoolRunnerPlugin\Subscriber;

use CoolRunnerPlugin\Controller\CoolRunnerAPI;
use CoolRunnerPlugin\Service\DeliveryService;
use CoolRunnerPlugin\Service\OrderService;
use CoolRunnerPlugin\Service\PrintService;
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

    /** @var DeliveryService */
    private $deliveryService;

    /** @var PrintService */
    private $printService;

    /** @var EntityRepositoryInterface */
    private $countryRepository;

    public function __construct(
        SystemConfigService $systemConfigService,
        LoggerInterface $logger,
        OrderService $orderService,
        DeliveryService $deliveryService,
        PrintService $printService,
        EntityRepositoryInterface $countryRepository
    )
    {
        $this->logger = $logger;
        $this->systemConfigService = $systemConfigService;
        $this->apiClient = new CoolRunnerAPI($systemConfigService);
        $this->orderService = $orderService;
        $this->deliveryService = $deliveryService;
        $this->printService = $printService;
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
        if($event->getStateEventName() == 'state_enter.order_delivery.state.shipped') {
            /** @var OrderEntity $order */
            $tempOrder = $this->deliveryService->getDeliveryById($event->getTransition()->getEntityId(), $event->getContext())->getOrder();

            /** @var OrderEntity $order */
            $order = $this->orderService->readData($event->getContext(), $tempOrder->getId());

            // Get country
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('id', $order->addresses->first()->countryId));
            $country = $this->countryRepository->search($criteria, $event->getContext())->first();

            // Create shipment
            $response = $this->apiClient->createShipment($order, $country);

            if(isset($response['body']->package_number) AND $response['body']->package_number != "") {
                $this->deliveryService->writeData($event->getContext(), $response['delivery_id'], $response['body']->package_number);

                if($this->systemConfigService->get('CoolRunnerPlugin.config.autoprint')) {
                    $printer = $this->printService->getPrinterById($event->getContext(), $this->systemConfigService->get('CoolRunnerPlugin.config.printer'));
                    $this->apiClient->printLabel($response['body']->package_number, $printer->getName());
                }
            }
        }
    }
}