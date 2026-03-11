<?php

return [
    'sections' => [
        [
            'title' => 'Candidate Access',
            'description' => 'Configure how candidates authenticate to access exams.',
            'fields' => [
                'candidate_login_mode',
            ],
        ],
    ],

    'fields' => [
        'candidate_login_mode' => [
            'type' => 'radio',
            'label' => 'Candidate Login Type',
            'default' => 'registration_number',
            'rules' => ['required', 'string', 'in:email_password,registration_number'],
            'options' => [
                [
                    'value' => 'registration_number',
                    'title' => 'Registration Number only',
                    'description' => 'Candidates will log in using only their registration number.',
                ],
                [
                    'value' => 'email_password',
                    'title' => 'Email + Password',
                    'description' => 'Candidates will log in using email and password.',
                ],
            ],
        ],
    ],
];
