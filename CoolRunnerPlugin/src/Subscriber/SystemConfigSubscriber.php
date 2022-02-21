<?php declare(strict_types=1);

namespace CoolRunnerPlugin\Subscriber;

use CoolRunnerPlugin\Controller\CoolRunnerAPI;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SystemConfigSubscriber implements EventSubscriberInterface
{
    /** @var SystemConfigService */
    private SystemConfigService $systemConfigService;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var CoolRunnerAPI */
    private $apiClient;

    /**
     * @param SystemConfigService $systemConfigService
     * @param LoggerInterface $logger
     */
    public function __construct(SystemConfigService $systemConfigService, LoggerInterface $logger)
    {
        $this->systemConfigService = $systemConfigService;
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
        foreach ($event->getPayloads() as $payload) {
            switch ($payload['configurationKey']) {
                case 'CoolRunnerPlugin.config.smartcheckouttoken':
                    $this->connectSmartCheckout($payload['configurationValue']);
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

}