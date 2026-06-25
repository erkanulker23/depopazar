<?php
require __DIR__ . '/env.php';
return [
    'app_name'     => 'Depo ve Nakliye Takip',
    'timezone'     => 'Europe/Istanbul',
    'session_name' => 'depopazar_session',

    // Web Push – php artisan vapid:generate ile üretin, Forge Environment'a ekleyin
    'vapid_public_key'  => getenv('VAPID_PUBLIC_KEY') ?: '',
    'vapid_private_key' => getenv('VAPID_PRIVATE_KEY') ?: '',
    'push_contact_email' => getenv('PUSH_CONTACT_EMAIL') ?: 'noreply@depopazar.com',
];
