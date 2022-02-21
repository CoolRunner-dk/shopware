<?php

namespace CoolRunnerPlugin\Controller;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class CoolRunnerAPI
{
    /** @var SystemConfigService $systemConfigService */
    private SystemConfigService $systemConfigService;

    /** @var Client $restClient */
    private Client $restClient;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
        $this->restClient = new Client();
    }

    public function connect($installation_token, $shop_name, $shop_url)
    {
        $request = new Request(
            'POST',
            'https://api.smartcheckout.coolrunner.dk?activation_token='.$installation_token,
            ['Content-Type' => 'application/json'],
            json_encode([
                'activation_code' => $installation_token,
                'name' => $shop_name,
                'platform' => 'Shopware',
                'version' => '1.0.0',
                'shop_url' => $shop_url,
                'pingback_url' => $shop_url . '/ping'
            ])
        );

        $response = $this->restClient->send($request);

        return json_decode($response->getBody()->getContents());
    }

    public function validate($data)
    {
        $request = new Request(
            'POST',
            'https://api.smartcheckout.coolrunner.dk?shop_token=' . $this->systemConfigService->get('CoolRunnerPlugin.config.shoptoken'),
            ['Content-Type' => 'application/json'],
            json_encode($data)
        );

        $response = $this->restClient->send($request);

        return json_decode($response->getBody()->getContents());
    }
}