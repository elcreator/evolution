<!-- User settings -->
<div class="tab-page" id="tabPage4">
    <h2 class="tab">{{ __('global.settings_email_templates') }}</h2>
    <script type="text/javascript">tpSettings.addTabPage(document.getElementById('tabPage4'));</script>
    <div class="container container-body">

        @include('manager::form.input', [
            'name' => 'emailsender',
            'label' => __('global.emailsender_title'),
            'small' => '[(emailsender)]',
            'value' => $settings['emailsender'],
            'attributes' => 'onchange="documentDirty=true;" maxlength="255"',
            'comment' => __('global.emailsender_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'email_sender_method',
            'label' => __('global.email_sender_method'),
            'small' => '[(email_sender_method)]',
            'value' => $settings['email_sender_method'],
            'options' => [
                1 => __('global.auto'),
                0 => __('global.use_emailsender')
            ],
            'comment' => __('global.email_sender_method_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'email_method',
            'label' => __('global.email_method_title'),
            'small' => '[(email_method)]',
            'value' => $settings['email_method'],
            'options' => [
                'mail' => [
                    'text' => __('global.email_method_mail'),
                    'attributes' => 'id="useMail"'
                ],
                'smtp' => [
                    'text' => __('global.email_method_smtp'),
                    'attributes' => 'id="useSmtp"'
                ]
            ],
            'comment' => __('global.email_sender_method_message')
        ])

        <div class="split my-1"></div>

        <div class="smtpRow" @if($settings['email_method'] == 'mail') style="display: none;" @endif>
            @include('manager::form.radio', [
                'name' => 'smtp_auth',
                'label' => __('global.smtp_auth_title'),
                'small' => '[(smtp_auth)]',
                'value' => $settings['smtp_auth'],
                'options' => [
                    1 => __('global.yes'),
                    0 => __('global.no')
                ]
            ])

            <div class="split my-1"></div>

            @include('manager::form.radio', [
                'name' => 'smtp_autotls',
                'label' => __('global.smtp_autotls_title'),
                'small' => '[(smtp_autotls)]',
                'value' => $settings['smtp_autotls'] ?? 0,
                'options' => [
                    1 => __('global.yes'),
                    0 => __('global.no')
                ]
            ])

            <div class="split my-1"></div>

            @include('manager::form.select', [
                'name' => 'smtp_secure',
                'label' => __('global.smtp_secure_title'),
                'small' => '[(smtp_secure)]',
                'value' => $settings['smtp_secure'],
                'attributes' => 'onChange="documentDirty=true;" size="1"',
                'options' => [
                    'none' => __('global.no'),
                    'ssl' => 'SSL',
                    'tls' => 'TLS',
                ]
            ])

            <div class="split my-1"></div>

            @include('manager::form.input', [
                'name' => 'smtp_host',
                'label' => __('global.smtp_host_title'),
                'small' => '[(smtp_host)]',
                'value' => $settings['smtp_host'],
                'attributes' => 'onchange="documentDirty=true;" maxlength="255"'
            ])

            <div class="split my-1"></div>

            @include('manager::form.input', [
                'name' => 'smtp_port',
                'label' => __('global.smtp_port_title'),
                'small' => '[(smtp_port)]',
                'value' => $settings['smtp_port'],
                'attributes' => 'onchange="documentDirty=true;" maxlength="255"'
            ])

            <div class="split my-1"></div>

            @include('manager::form.input', [
                'name' => 'smtp_username',
                'label' => __('global.smtp_username_title'),
                'small' => '[(smtp_username)]',
                'value' => $settings['smtp_username'],
                'attributes' => 'onchange="documentDirty=true;" maxlength="255"'
            ])

            <div class="split my-1"></div>

            @include('manager::form.input', [
                'name' => 'smtppw',
                'label' => __('global.smtp_password_title'),
                'small' => '[(smtppw)]',
                'value' => '********************',
                'attributes' => 'onchange="documentDirty=true;" maxlength="255" autocomplete="off"'
            ])

            <div class="split my-1"></div>
        </div>

        @include('manager::form.input', [
            'name' => 'emailsubject',
            'id' => 'emailsubject_field',
            'for' => 'emailsubject_field',
            'label' => __('global.emailsubject_title') . '<br>' .
                __('global.update_settings_from_language') .
                view('manager::form.selectElement', [
                    'name' => 'reload_emailsubject',
                    'id' => 'reload_emailsubject_select',
                    'class' => 'form-control-sm',
                    'attributes' => 'onchange="confirmLangChange(this, \'emailsubject_default\', \'emailsubject_field\');"',
                    'first' => [
                        'text' => __('global.language_title')
                    ],
                    'options' => $langKeys,
                    'as' => 'values',
                    'ucwords' => true
                ]) .
                view('manager::form.inputElement', [
                    'type' => 'hidden',
                    'name' => 'emailsubject_default',
                    'id' => 'emailsubject_default_hidden',
                    'value' => addslashes(__('global.emailsubject_default'))
                ])
            ,
            'small' => '[(emailsubject)]',
            'value' => $settings['emailsubject'],
            'attributes' => 'onchange="documentDirty=true;" maxlength="255"',
            'comment' => __('global.emailsubject_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.textarea', [
            'name' => 'websignupemail_message',
            'id' => 'websignupemail_message_textarea',
            'for' => 'websignupemail_message_textarea',
            'label' => __('global.websignupemail_title') . '<br>' .
                __('global.update_settings_from_language') .
                view('manager::form.selectElement', [
                    'name' => 'reload_websignupemail_message',
                    'id' => 'reload_websignupemail_message_select',
                    'class' => 'form-control-sm',
                    'attributes' => 'onchange="confirmLangChange(this, \'system_email_websignup\', \'websignupemail_message_textarea\');"',
                    'first' => [
                        'text' => __('global.language_title')
                    ],
                    'options' => $langKeys,
                    'as' => 'values',
                    'ucwords' => true
                ]) .
                view('manager::form.inputElement', [
                    'type' => 'hidden',
                    'name' => 'system_email_websignup_default',
                    'id' => 'system_email_websignup_hidden',
                    'value' => addslashes(__('global.system_email_websignup'))
                ])
            ,
            'small' => '[(websignupemail_message)]',
            'value' => $settings['websignupemail_message'],
            'rows' => 5,
            'attributes' => 'onchange="documentDirty=true;"',
            'comment' => __('global.websignupemail_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.textarea', [
            'name' => 'webpwdreminder_message',
            'id' => 'system_email_webreminder_textarea',
            'for' => 'system_email_webreminder_textarea',
            'label' => __('global.webpwdreminder_title') . '<br>' .
                __('global.update_settings_from_language') .
                view('manager::form.selectElement', [
                    'name' => 'reload_system_email_webreminder_message',
                    'id' => 'reload_system_email_webreminder_select',
                    'class' => 'form-control-sm',
                    'attributes' => 'onchange="confirmLangChange(this, \'system_email_webreminder\', \'system_email_webreminder_textarea\');"',
                    'first' => [
                        'text' => __('global.language_title')
                    ],
                    'options' => $langKeys,
                    'as' => 'values',
                    'ucwords' => true
                ]) .
                view('manager::form.inputElement', [
                    'type' => 'hidden',
                    'name' => 'system_email_webreminder_default',
                    'id' => 'system_email_webreminder_hidden',
                    'value' => addslashes(__('global.system_email_webreminder'))
                ])
            ,
            'small' => '[(webpwdreminder_message)]',
            'value' => $settings['webpwdreminder_message'],
            'rows' => 5,
            'attributes' => 'onchange="documentDirty=true;"',
            'comment' => __('global.webpwdreminder_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'allow_multiple_emails',
            'label' => __('global.allow_multiple_emails_title'),
            'small' => '[(allow_multiple_emails)]',
            'value' => $settings['allow_multiple_emails'],
            'options' => [
                1 => __('global.yes'),
                0 => __('global.no')
            ],
            'comment' => __('global.allow_multiple_emails_message')
        ])

        <div class="split my-1"></div>

        {!! get_by_key($tabEvents, 'OnUserSettingsRender') !!}
    </div>
</div>
