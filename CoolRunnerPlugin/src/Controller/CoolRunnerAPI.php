<?php

namespace CoolRunnerPlugin\Controller;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Shopware\Core\Content\Product\ProductEntity;
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

    public function createShipment($order, $country)
    {
         switch ($this->systemConfigService->get('CoolRunnerPlugin.config.warehouse')) {
             case 'internal':
                 return $this->createV3($order, $country);
                 break;
             case 'external':
                 return $this->createWMS($order, $country);
                 break;
            default:
                return false;
                break;
        };
    }

    private function createV3($order, $country = null)
    {
        $customerAddress = $order->addresses->first();
        $customerInformation = $order->orderCustomer;
        $orderLines = $order->lineItems;

        $data = [
            'sender' => [
                'name' => $this->systemConfigService->get('CoolRunnerPlugin.config.sendershop'),
                'attention' => '',
                'street1' => $this->systemConfigService->get('CoolRunnerPlugin.config.senderstreet1'),
                'street2' => $this->systemConfigService->get('CoolRunnerPlugin.config.senderstreet2'),
                'zip_code' => $this->systemConfigService->get('CoolRunnerPlugin.config.senderzipcode'),
                'city' => $this->systemConfigService->get('CoolRunnerPlugin.config.sendercity'),
                'country' => $this->systemConfigService->get('CoolRunnerPlugin.config.sendercountry'),
                'phone' => $this->systemConfigService->get('CoolRunnerPlugin.config.senderphone'),
                'email' => $this->systemConfigService->get('CoolRunnerPlugin.config.senderemail')
            ],
            'receiver' => [
                'name' => ($customerAddress->company != '') ? $customerAddress->company : $customerAddress->firstName . ' ' . $customerAddress->lastName,
                'attention' => ($customerAddress->company != '') ? $customerAddress->firstName . ' ' . $customerAddress->lastName : '',
                'street1' => $customerAddress->street,
                'street2' => '',
                'zip_code' => $customerAddress->zipcode,
                'city' => $customerAddress->city,
                'country' => ($country != null) ? $country->iso : $customerAddress->country,
                'phone' => '',
                'email' => $customerInformation->email,
                'notify_sms' => '',
                'notify_email' => $customerInformation->email
            ],
            'length' => 15,
            'width' => 15,
            'height' => 15,
            'weight' => 1000,
            'carrier' => 'bring',
            'carrier_product' => 'private',
            'carrier_service' => 'delivery',
            'reference' => $order->orderNumber,
            'comment' => '',
            'description' => '',
            'label_format' => $this->systemConfigService->get('CoolRunnerPlugin.config.printformat'),
            'servicepoint_id' => 0,
            'order_lines' => []
        ];

        foreach ($orderLines as $orderLine) {
            $data['order_lines'][] = [
                'qty' => $orderLine->quantity,
                'item_number' => $orderLine->payload['productNumber']
            ];
        }

        return $data;

//        $request = new Request(
//            'POST',
//            'https://api.coolrunner.dk/v3/shipments',
//            ['Content-Type' => 'application/json'],
//            json_encode($data)
//        );
//
//        $this($data);
//
//        $response = $this->restClient->send($request);
//
//        return $response;
    }

    private function createWMS($order, $country)
    {
        return [];
    }
}