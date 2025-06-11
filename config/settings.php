<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings Definitions
    |--------------------------------------------------------------------------
    |
    | Keyed by “code”, each entry defines the label, field type,
    | validation rules, default value, and which scopes (global/team)
    | it belongs to.
    |
    */

    'definitions' => [

        'SUPPORT_EMAIL' => [
            'label'     => 'Support e-mail',
            'type'      => 'email',
            'rules'     => ['nullable', 'email'],
            'default'   => null,
            'scopes'    => ['global'],
        ],

        'TASK_PRIORITY' => [
            'label'   => 'Kleur van taakprioriteit',
            'type'    => 'group',            // a custom “group” type
            'scopes'  => ['global', 'team'],
            'default' => [
                'Low'    => ['time' => null, 'color' => null],
                'Medium' => ['time' => null, 'color' => null],
                'High'   => ['time' => null, 'color' => null],
            ],
            'config'  => [
                // the three fixed levels
                'levels' => ['Low', 'Medium', 'High'],

                // each “field” that every level gets
                'fields' => [
                    'time' => [
                        'type'        => 'text',
                        'label'       => 'Tijd (minuten)',
                        'placeholder' => 'e.g. 60',
                        'rules'       => ['required', 'numeric', 'min:1'],
                    ],
                    'color' => [
                        'type'  => 'color',
                        'label' => 'Kleur',
                        'rules' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                    ],
                ],
            ],
        ],

        // …add more settings here…

    ],

];
