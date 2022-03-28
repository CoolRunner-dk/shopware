<?php declare(strict_types=1);

namespace CoolRunnerPlugin\Subscriber;

use CoolRunnerPlugin\Controller\CoolRunnerAPI;
use CoolRunnerPlugin\Service\MethodsService;
use CoolRunnerPlugin\Service\PrintService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SystemConfigSubscriber implements EventSubscriberInterface
{
    /** @var SystemConfigService */
    private SystemConfigService $systemConfigService;

    /** @var PrintService */
    private PrintService $printService;

    /** @var MethodsService */
    private MethodsService $methodsService;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var CoolRunnerAPI */
    private $apiClient;

    /**
     * @param SystemConfigService $systemConfigService
     * @param LoggerInterface $logger
     */
    public function __construct(SystemConfigService $systemConfigService, PrintService $printService, MethodsService $methodsService, LoggerInterface $logger)
    {
        $this->systemConfigService = $systemConfigService;
        $this->printService = $printService;
        $this->methodsService = $methodsService;
        $this->logger = $logger;
        $this->apiClient = new CoolRunnerAPI($systemConfigService);
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            'system_config.written' => 'onSystemConfigWritten'
        ];
    }

    /**
     * @param EntityWrittenEvent $event
     */
    public function onSystemConfigWritten(EntityWrittenEvent $event)
    {
        //$this->printService->writeData($event->getContext());

        foreach ($event->getPayloads() as $payload) {
            switch ($payload['configurationKey']) {
                case 'CoolRunnerPlugin.config.smartcheckouttoken':
                    $this->connectSmartCheckout($payload['configurationValue']);
                    $this->getPrinters($event->getContext());
                    $this->getShippingMethods($event->getContext());
                    break;
            }
        }
    }

    /**
     * @param $installation_token
     */
    public function connectSmartCheckout($installation_token)
    {
        $install = $this->apiClient->connect(
            $installation_token,
            $this->systemConfigService->get('CoolRunnerPlugin.config.sendershop'),
            $this->systemConfigService->get('CoolRunnerPlugin.config.sendershopurl')
        );

        if(isset($install->status) AND $install->status == "ok") {
            $this->systemConfigService->set('CoolRunnerPlugin.config.apiemail', $install->shop_info->integration_email);
            $this->systemConfigService->set('CoolRunnerPlugin.config.apitoken', $install->shop_info->integration_token);
            $this->systemConfigService->set('CoolRunnerPlugin.config.shoptoken', $install->shop_info->shop_token);
        }
    }

    public function getPrinters($context)
    {
        $printers = $this->apiClient->getPrinters();

        foreach ($printers as $printer) {
            $this->printService->writeData($context, $printer->name, $printer->alias, $printer->description);
        }
    }

    public function getShippingMethods($context)
    {
        $methods = $this->apiClient->getMethods();

        foreach ($methods as $method) {
            $this->methodsService->writeData($context, $method['name'], $method['cps'], $method['from'], $method['to']);
        }

        // $this->logger->debug('CoolRunnerTest: ' . json_encode($methods));

        //$this->methodsService->writeData($context);
    }

}