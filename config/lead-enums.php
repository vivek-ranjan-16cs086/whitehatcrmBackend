<?php

return [
    'status' => [
        'description' => 'Current stage of the lead.',
        'default' => 'active',
        'values' => ['active', 'not_connected', 'not_available', 'follow_up', 'visit', 'deactive', 'follow_up_expired', 'follow_up_complete', 'booked'],
    ],
    'type' => [
        'description' => 'Lead interest or readiness to buy.',
        'default' => 'hot',
        'values' => ['hot', 'cold', 'warm', 'ready-to-buy'],
    ],
    'visit_status' => [
        'description' => 'Progress of the lead’s site visit.',
        'default' => 'not_scheduled',
        'values' => ['not_scheduled', 'scheduled', 'completed', 'visited', 'cancelled', 're-visit-plan', 're-visit-done'],
    ],
    'lead_source' => [
        'description' => 'Channel that generated the lead.',
        'default' => null,
        'values' => [
            'Calling', 'Whitehat', 'WHATSAPP', 'CATCHING', 'Youtube', '4QT', '360youtube', '99Acre',
            'Advert IQ', 'Referral', 'Other', 'Facebook', 'Instagram', 'Housing.com', 'PPC', 'Google',
            '360propguide Website', 'Static Websites', 'IVR', 'Walk-in', 'Broker', 'Dealer', 'Print Media', 'Out Door Media',
        ],
    ],
    'inventory' => [
        'description' => 'Property type the lead is interested in.',
        'default' => null,
        'values' => ['1 BHK', '2 BHK', '3 BHK', '4 BHK', '5 BHK', '6 BHK', 'Plots', 'Villa', 'Studio Apartment', 'Shops'],
    ],
    'deactivate_reason' => [
        'description' => 'Reason the lead was deactivated.',
        'default' => null,
        'values' => ['Plot or Villa', 'Budget', 'Location', 'Ready To Move', 'Rent', 'Invalid Mobile Number', 'Others', 'Switch Off'],
    ],
];
