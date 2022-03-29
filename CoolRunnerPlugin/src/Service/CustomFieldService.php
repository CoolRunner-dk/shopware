<?php

namespace CoolRunnerPlugin\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class CustomFieldService
{
    private EntityRepositoryInterface $customFieldRepository;

    public function __construct(EntityRepositoryInterface $customFieldRepository)
    {
        $this->customFieldRepository = $customFieldRepository;
    }

    public function createSWCustomFields(Context $context)
    {
        // Create customs fields
        $this->createCustomFields($context);
    }

    public function createCustomFields(Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'coolrunner_customs'));

        if(empty($this->customFieldRepository->search($criteria, $context)->first())) {
            $this->customFieldRepository->create([
                [
                    'name' => 'coolrunner_customs',
                    // 'global' => true,
                    'config' => [
                        'label' => [
                            'de-DE' => 'CoolRunner Customs',
                            'en-GB' => 'CoolRunner Customs'
                        ]
                    ],
                    'customFields' => [
                        [
                            'name' => 'coolrunner_customs_hscode_from',
                            'label' => "HS Code (From)",
                            'type' => CustomFieldTypes::TEXT,
                            'config' => [
                                'label' => [
                                    'de-DE' => 'HS Code (From)',
                                    'en-GB' => 'HS Code (From)'
                                ]
                            ]
                        ],
                        [
                            'name' => 'coolrunner_customs_hscode_to',
                            'label' => "HS Code (To)",
                            'type' => CustomFieldTypes::TEXT,
                            'config' => [
                                'label' => [
                                    'de-DE' => 'HS Code (To)',
                                    'en-GB' => 'HS Code (To)'
                                ]
                            ]
                        ],
                        [
                            'name' => 'coolrunner_customs_origin_country',
                            'label' => "Origin Country",
                            'type' => CustomFieldTypes::ENTITY,
                            'config' => [
                                'entity' => 'country',
                                'componentName' => "sw-entity-single-select",
                                'label' => [
                                    'de-DE' => 'Origin Country',
                                    'en-GB' => 'Origin Country'
                                ]
                            ]
                        ]
                    ],
                    'relations' => [[
                        'entityName' => 'product'
                    ]],
                ],
                [
                    'name' => 'coolrunner_cps',
                    // 'global' => true,
                    'config' => [
                        'label' => [
                            'de-DE' => 'CoolRunner Methods',
                            'en-GB' => 'CoolRunner Methods'
                        ]
                    ],
                    'customFields' => [
                        [
                            'name' => 'coolrunner_methods',
                            'label' => "CoolRunner Methods",
                            'type' => CustomFieldTypes::ENTITY,
                            'config' => [
                                'entity' => 'coolrunner_methods',
                                'componentName' => "sw-entity-single-select",
                                'label' => [
                                    'de-DE' => 'CoolRunner Methods',
                                    'en-GB' => 'CoolRunner Methods'
                                ]
                            ]
                        ]
                    ],
                    'relations' => [[
                        'entityName' => 'shipping_method'
                    ]],
                ]
            ], $context);
        }
    }


}