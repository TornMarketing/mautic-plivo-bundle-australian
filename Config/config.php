<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Plivo',
    'description' => 'Plivo Sms integration (australian tweak)',
    'author'      => 'tornmarketing.com.au',
    'version'     => '1.2.0',
    'services' => [
        'events'  => [],
        'forms'   => [
        ],
        'helpers' => [],
        'other'   => [
            'mautic.sms.transport.plivo' => [
                'class'     => \MauticPlugin\MauticPlivoBundle\Services\PlivoApi::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'monolog.logger.mautic',
                    'mautic.http.client'
                ],
                'alias' => 'mautic.sms.config.transport.plivo',
                'tag'          => 'mautic.sms_transport',
                'tagArguments' => [
                    'integrationAlias' => 'Plivo',
                ],
            ],
        ],
        'models'       => [],
        'integrations' => [
            'mautic.integration.plivo' => [
                'class' => \MauticPlugin\MauticPlivoBundle\Integration\PlivoIntegration::class,
            'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
        ],
    ],
    'routes'     => [],
    'menu'       => [
        'main' => [
            'items' => [
                'mautic.sms.smses' => [
                    'route'    => 'mautic_sms_index',
                    'access'   => ['sms:smses:viewown', 'sms:smses:viewother'],
                    'parent'   => 'mautic.core.channels',
                    'checks'   => [
                        'integration' => [
                            'Mtalkz' => [
                                'enabled' => true,
                            ],
                        ],
                    ],
                    'priority' => 70,
                ],
            ],
        ],
    ],
    'parameters' => [],
];
