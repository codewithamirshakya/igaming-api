<?php

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'serverSalt' => 'Qq959helou!',
    'jwt' => [
        'issuer' => 'https://api.example.com',  //name of your project (for information only)
        'audience' => 'https://frontend.example.com',  //description of the audience, eg. the website using the authentication (for info only)
        'id' => 'UNIQUE-JWT-IDENTIFIER',  //a unique identifier for the JWT, typically a random string
        'expire' => 300,  //the short-lived JWT token is here set to expire after 5 min.
    ],
    'allowed_ips' => [
        '127.0.0.1', '::1', //localhost
        '10.254.1.10', //staging
        '10.254.2.9', // production FE 2
        '10.0.13.50', //production
        '10.0.13.64' //production
    ],
    'endpoint' => [
        'feapi' => [
            'base_url' => 'http://localhost/feapi'
        ],
        'igaming-api' => [
            'base_url' => 'http://10.15.48.5/igaming-api/web/index.php'
        ],
        'arps' =>[
            'base_url' => 'http://10.0.13.52/wapi',
            'dashboard_url' => 'http://10.0.13.52:9001/wapi',
            'api_key'  => 'as78deftau8h3987rgiouahsbncfa378hopisuhfao3gbdoiuerfbw',
            'api_username' => 'testing',
            'api_password' => 'testing',
        ],
        'velagaming' => [
            'base_url'    => 'https://bo-stage.velachip.com/api',
            'lobby_url'   => 'https://bo-stage.velachip.com/lobby',
            'operator_id' => '97e9d9beb08cdfcc54ab3714f0c944ea',
            'currency'    => 'PHP',
            'prefix'      => 'SAVG',
        ],
        //offercraft staging
        'offercraft' => [
            'base_url'      => 'https://dev-c-api.reward-access.com/api/ext',
            'reward_url'    => 'https://dev-c.reward-access.com',
            'api_key'       => 'hS4CvxOAl%2Bdgdd5FPwTJKOG0apw5gFKK5qb4i%2BgfNIKtav4qjvb5wE5%2FlvPUpPIDby8RARZLafrum7gwwd1xfQ%3D%3D',
            'lobby_token'   => 'G9J8O',
            'platform'      => 'SolaireOnline',
            'onsite_ip'            =>  ['103.79.110.49', // PLDT
                                '103.79.111.71' //GLOBE
                                ]
        ],
        //solaire freeplay
        'solaire' => [
            'config' => [
                'base_url'              => 'https://svpplt.solaireresort.com:9777/ws/',
                'username'              => 'ARPSUser',
                'password'              => 'Fvfym3z8wKJ2WhQV3AJNmtdz@vZ',
                'default_authorizer'    => 'MACHV',
                'opmg_authorizer'       => 'SOOPG',
                'studio_authorizer'     => 'SOSTU',
            ],
            'url'    => [
                //'peso_inquiry'    => 'GetPatronDemographics_QAVS/1.0/Solaire_Patron_Receive/resources/v1/getPatronDemographics', //old url
                'peso_inquiry'    => 'solairePesoBonusPesoInquiry_QAVS/1.0/Solaire_Live_Account/resources/v1/solairePesoBonusPesoInquiry',
                'peso_adjustment' => 'SolairePesoAndBonusPesoAdjustment_QAVS/1.0/Solaire_ScratchCardGame/resources/v1/SolairePesoAndBonusPesoAdjustment',
                'nccu_inquiry'    => 'NCCUInquiry_QAVS/1.0/Solaire_Live_Account/resources/v1/NCCUInquiry',
                'nccu_adjustment' => 'NCCUWithdrawalAndDeposit_QAVS/1.0/Solaire_Live_Account/resources/v1/NCCUWithdrawalAndDeposit',
            ]
        ],
        'arpslot' =>[
            'https_url' => "https://arps-staging.solaireonlinecasino.com:2083/LiveSlotNewService/",
            'internal_url' => 'http://10.0.13.53:9004/LiveSlotNewServiceInternal/',
            'api_key'  => 'sjn3la,sa..2p-2jdn21iajm19802kla',
            // 'game_data' => $game_data,
        ],
        'jackpotmeter' =>[
            'base_url'           => 'https://gu43y9xsm7.execute-api.ap-southeast-1.amazonaws.com/prod/jackpotmeter/read',
            'api_username'       => 'Jackpotmeter',
            'api_password'       => 'JM:DEV:9918ktty71kaikzu',
            'slotjackpot_mapper' => [
                4   => 3267, // Duo Fu Duo Cai
                5   => 1213, // Lightning Link
                8   => 1609, // Good Fortune Link 20M
                9   => 913,  // FA FA FA Php 1.00 LINK B3
                10  => 105,  // DRAGONS ON THE LAKE 4
                11  => 571,  // Dragon Riches
                12  => 546,  // Prosperity Exteme v4
                13  => 1830, // Fu Lai Cai Lai 15M
            ], // staging
            //'slotjackpot_mapper' => $slotjackpot_params['production_slotjackpot_mapper'], //production
        ],
        //solaire service
        'solaire_service' => [
            'offering_public_url'        => 'https://onlineoffers-stg.solaireresort.com/',
            'offering_encrypt_secret'    => 'VmYq3t6w9y$B&E)H@McQfTjWnZr4u7x!',
            'pp_public_url'              => 'http://onlinepayment-qa.solaireresort.com.s3-website-ap-southeast-1.amazonaws.com',
            'pp_encrypt_secret'          => '8x/A?D(G+KbPdSgVkYp3s6v9y$B&E)H@',
            'encrypt_url'                => 'https://ssapi-stg.solaireresort.com/encrypt',
            'encrypt_username'           => 'p2quXHevAzFw',
            'encrypt_password'           => '4u3DdCFSJ4SC',
        ],
        //offercraft production
        // 'offercraft' => [
        //     'base_url'      => 'https://c-api.reward-access.com/api/ext',
        //     'reward_url'    => 'https://dev-c.reward-access.com',
        //     'api_key'       => 'Rr2FmYWQ6tUa9ZNfYv%2B3bWlVXyZp8t7%2FtKxwhRwbFRgzJHr3I8%2BEHV9t%2BYsalfLEYBGJEE3PKMlz0XetJ%2BzyWQ%3D%3D',
        //     'lobby_token'   => 'AA5Wlt',
        //     'platform'      => 'SolaireOnline',
        //     'onsite_ip'            =>  ['103.79.110.49', // PLDT
        //                         '103.79.111.71' //GLOBE
        //                         ]
        // ]
        //arpstudio staging
        'arpstudio' =>[
            'base_url'               => 'https://arpstg-gatewayapi.solaireonlinecasino.com/api/',
            'app_id'                 => 'xtdPADSU5K8E',
            'app_key'                => 'B5676A4C02D598C70C5819ED9841C016',
            'game_code_mapper'       => [
                //Production
                '1'	   => "G-OSS001",
                '2'	   => "G-OSS001",
                '3'	   => "G-OSS002",
                '4'	   => "G-OSS002",
                '5'	   => "G-OSS003",
                '6'	   => "G-OSS003",
                '7'	   => "G-OSS004",
                '8'	   => "G-OSS004",
                '9'	   => "G-OSS005",
                '10'   => "G-OSS005",
                '11'   => "G-OSS006",
                '12'   => "G-OSS006",
                '13'   => "G-OSS007",
                '14'   => "G-OSS007",
                '15'   => "G-OSS008",
                '16'   => "G-OSS008",
                '17'   => "G-ORS3007",
                '18'   => "G-SICBO1",
                '19'   => "G-POS3005",
                '20'   => "G-POM3006",
                '21'   => "G-OSS3001",
                '22'   => "G-OSS3002",
                '23'   => "G-OSS3003",
                '24'   => "G-OSS3004",
                '25'   => "G-ORS3008",
                '26'   => "G-SICBO2",
        
                //Staging
                '2821' => "G-OSS001",
                '2822' => "G-OSS001",
                '2823' => "G-OSS002",
                '2824' => "G-OSS002",
                '2825' => "G-OSS003",
                '2826' => "G-OSS003",
                '2827' => "G-OSS004",
                '2828' => "G-OSS004",
                '2829' => "G-OSS005",
                '2830' => "G-OSS005",
                '2831' => "G-OSS006",
                '2832' => "G-OSS006",
                '2833' => "G-OSS007",
                '2834' => "G-OSS007",
                '2835' => "G-OSS008",
                '2836' => "G-OSS008",
                '2837' => "G-8F29F991D3B3",
                '2838' => "G-572B22BB6CIT",
                '2839' => "G-SICBO1",
                '2840' => "G-POS3005",
                '2841' => "G-POM3006",
                '2842' => "G-OSS3001",
                '2843' => "G-OSS3002",
                '2844' => "G-OSS3003",
                '2845' => "G-OSS3004",
                '2846' => "G-SICBO2",
            ],
            'sig_game_code_mapper'   => [
                //Production
                '4'    => "OSS",
                '6'    => "OSS",
                '8'    => "OSS",
                '9'    => "OMD",
                '11'   => "OMD",
                '14'   => "POM",
                '16'   => "OSS",
                '17'   => "OSS",
                '18'   => "OMD",
                '19'   => "OSS",
                '20'   => "OSS",
                '21'   => "POS",
                '22'   => "OMD",
                '23'   => "ORS",
                '24'   => "OSB",
                '26'   => "OSB",
        
                //Staging - Fake game code as staging are not suppose to be rate, testing purpose
                '2822' => "SS",
                '2824' => "SS",
                '2826' => "SS",
                '2828' => "SS",
                '2830' => "SS",
                '2832' => "SS",
                '2834' => "SS",
                '2836' => "SS",
                '2837' => "RS",
                '2838' => "RS",
                '2839' => "SB",
                '2840' => "PM",
                '2841' => "PM",
                '2842' => "SS",
                '2843' => "SS",
                '2844' => "SS",
                '2845' => "SS",
                '2846' => "SB",
            ],
            'dealer_icon'            => "https://arpstg-cdn.solaireonlinecasino.com"
        ],
        //opmg sig php staging
        'opmg' =>[
            'base_url'  => 'http://10.0.13.61/opapistaging',
            'host_id'   => 'SiG',
            'lobby_url' => 'http://bo-stage.velachip.com/lobby',
            'currency'  => 'PHP',
        ],
        //opapi staging
        'opapi' =>[
            'base_url'  => 'http://10.0.13.61/opapistaging/',
        ],
    ],
    // SiG staging wallet configuration
    'pushcredit' => [
        //opmg sig php staging
        'opmg'  =>[
            'enter'     => true, // push to OPMG on enter game
            'money_in'  => false, // push to OPMG on money in
            'login'     => false, // push to OPMG on login
        ],
        'arps'  =>[
            'start'     => true, // push to ARPS on slot start
        ],
        'arpstudio' =>[
            'start'     => true, // push to ARP-Studio on login
        ],
    ]
];
