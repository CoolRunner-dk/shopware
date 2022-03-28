<?php

namespace CoolRunnerPlugin\Controller;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class CoolRunnerAPI
{
    // TODO: Add shipping methods to settings
    // TODO: Get customs information from custom_fields
    // TODO: Error handling
    // TODO: Check if possible to create condition from plugin

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

    public function createShipment($order, $country, $warehouse = '')
    {
         switch ($this->systemConfigService->get('CoolRunnerPlugin.config.warehouse')) {
             case 'internal':
                 return $this->createV3($order, $country);
                 break;
             case 'external':
                 return $this->createWMS($order, $warehouse, $country);
                 break;
            default:
                return false;
                break;
        };
    }

    private function createV3(OrderEntity $order, $country = null)
    {
        $customerAddress = $order->getAddresses()->first();
        $customerInformation = $order->getOrderCustomer();
        $orderLines = $order->getLineItems();

        // Get CoolRunner CPS
        $delivery = $order->getDeliveries()->first();
        $cps = $delivery->getShippingMethod()->getCustomFields()['coolrunner_cps'];
        $cps_exploded = explode('_', $cps);
        $carrier = $cps_exploded[0];
        $carrier_product = $cps_exploded[1];
        $carrier_service = $cps_exploded[2];

        // TODO: Get phone

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
                'phone' => 88888888,
                'email' => $customerInformation->email,
                'notify_sms' => '',
                'notify_email' => $customerInformation->email
            ],
            'length' => 15,
            'width' => 15,
            'height' => 15,
            'weight' => 1000,
            'carrier' => $carrier,
            'carrier_product' => $carrier_product,
            'carrier_service' => $carrier_service,
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

        $request = new Request(
            'POST',
            'https://api.coolrunner.dk/v3/shipments',
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($this->systemConfigService->get('CoolRunnerPlugin.config.apiemail').':'.$this->systemConfigService->get('CoolRunnerPlugin.config.apitoken'))
            ],
            json_encode($data)
        );

        $response = $this->restClient->send($request);

        return ['body' => json_decode($response->getBody()), 'delivery_id' => $delivery->getId()];
    }

    private function createWMS(OrderEntity $order, $warehouse, $country = null)
    {
        $customerAddress = $order->getAddresses()->first();
        $customerInformation = $order->getOrderCustomer();
        $orderLines = $order->getLineItems();

        // Get CoolRunner CPS
        $delivery = $order->getDeliveries()->first();
        $cps = $delivery->getShippingMethod()->getCustomFields()['coolrunner_cps'];
        $cps_exploded = explode('_', $cps);
        $carrier = $cps_exploded[0];
        $carrier_product = $cps_exploded[1];
        $carrier_service = $cps_exploded[2];

        // TODO: Get phone

        $data = [
            'warehouse' => $warehouse,
            'order_number' => $order->orderNumber,
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
                'phone' => 88888888,
                'email' => $customerInformation->email,
                'notify_sms' => '',
                'notify_email' => $customerInformation->email
            ],
            'length' => 15,
            'width' => 15,
            'height' => 15,
            'weight' => 1000,
            'carrier' => $carrier,
            'carrier_product' => $carrier_product,
            'carrier_service' => $carrier_service,
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

        $request = new Request(
            'POST',
            'https://api.coolrunner.dk/wms/orders',
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($this->systemConfigService->get('CoolRunnerPlugin.config.apiemail').':'.$this->systemConfigService->get('CoolRunnerPlugin.config.apitoken'))
            ],
            json_encode($data)
        );

        $response = $this->restClient->send($request);

        return ['body' => json_decode($response->getBody()), 'delivery_id' => $delivery->getId()];
    }

    public function getPrinters()
    {
        $request = new Request(
            'GET',
            'https://api.coolrunner.dk/printers',
            [
                'Authorization' => 'Basic ' . base64_encode($this->systemConfigService->get('CoolRunnerPlugin.config.apiemail').':'.$this->systemConfigService->get('CoolRunnerPlugin.config.apitoken'))
            ]
        );

        $response = $this->restClient->send($request);

        return json_decode($response->getBody());
    }

    public function getWarehouses()
    {
        $request = new Request(
            'GET',
            'https://api.coolrunner.dk/wms/warehouses',
            [
                'Authorization' => 'Basic ' . base64_encode($this->systemConfigService->get('CoolRunnerPlugin.config.apiemail').':'.$this->systemConfigService->get('CoolRunnerPlugin.config.apitoken'))
            ]
        );

        $response = $this->restClient->send($request);

        return json_decode($response->getBody());
    }

    public function printLabel($packageNumber, $printerAlias)
    {
        $data = [
            'package_number' => (string) $packageNumber
        ];

        $request = new Request(
            'POST',
            'https://api.coolrunner.dk/printers/'.$printerAlias.'/print',
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($this->systemConfigService->get('CoolRunnerPlugin.config.apiemail').':'.$this->systemConfigService->get('CoolRunnerPlugin.config.apitoken'))
            ],
            json_encode($data)
        );

        $response = $this->restClient->send($request);

        return json_decode($response->getBody());
    }

    public function getMethods()
    {
        $request = new Request(
            'GET',
            'https://api.coolrunner.dk/v3/products/' . $this->systemConfigService->get('CoolRunnerPlugin.config.sendercountry'),
            [
                'Authorization' => 'Basic ' . base64_encode($this->systemConfigService->get('CoolRunnerPlugin.config.apiemail').':'.$this->systemConfigService->get('CoolRunnerPlugin.config.apitoken'))
            ]
        );

        $response_json = $this->restClient->send($request)->getBody();
        $response = json_decode($response_json);
        $methods = [];

        foreach ($response as $receiver_country => $carriers) {
            foreach ($carriers as $carrier => $carrier_products) {
                foreach ($carrier_products as $product => $carrier_product) {
                    foreach ($carrier_product as $carrier_service) {

                        if(isset($carrier_service->services[0]->code) AND $carrier_service->services[0]->code != "") {
                            $cps = $carrier . '_' . $product . '_' . $carrier_service->services[0]->code;
                        } else {
                            $cps = $carrier . '_' . $product;
                        }

                        $methods[] = [
                            'name' => $carrier_service->title,
                            'cps' => $cps,
                            'from' => $this->systemConfigService->get('CoolRunnerPlugin.config.sendercountry'),
                            'to' => $receiver_country
                        ];
                    }
                }
            }
        }

        return $methods;
    }
}