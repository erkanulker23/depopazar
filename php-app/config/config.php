<?php
return [
    'app_name'     => 'DepoPazar',
    'timezone'     => 'Europe/Istanbul',
    'session_name' => 'depopazar_session',

    // Web Push (telefon/cihaz bildirimleri) – VAPID anahtarları. Oluşturmak için: php php-app/scripts/generate-vapid-keys.php
    'vapid_public_key'  => getenv('VAPID_PUBLIC_KEY') ?: '',
    'vapid_private_key' => getenv('VAPID_PRIVATE_KEY') ?: '',
    'push_contact_email' => getenv('PUSH_CONTACT_EMAIL') ?: 'noreply@depopazar.com',
];
