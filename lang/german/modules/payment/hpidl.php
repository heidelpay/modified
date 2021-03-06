<?php
$prefix = 'MODULE_PAYMENT_HPIDL_';

define($prefix.'TEXT_TITLE', 'IDeal');
define($prefix.'TEXT_DESC', 'IDeal &uuml;ber heidelpay GmbH');

define($prefix.'SECURITY_SENDER_TITLE', 'Sender ID');
define($prefix.'SECURITY_SENDER_DESC', 'Ihre heidelpay Sender ID');

define($prefix.'USER_LOGIN_TITLE', 'User Login');
define($prefix.'USER_LOGIN_DESC', 'Ihr heidelpay User Login');

define($prefix.'USER_PWD_TITLE', 'User Passwort');
define($prefix.'USER_PWD_DESC', 'Ihr heidelpay User Passwort');

define($prefix.'TRANSACTION_CHANNEL_TITLE', 'Channel ID');
define($prefix.'TRANSACTION_CHANNEL_DESC', 'Ihre heidelpay Channel ID');

define($prefix.'TRANSACTION_MODE_TITLE', 'Transaction Mode');
define($prefix.'TRANSACTION_MODE_DESC', 'W&auml;hlen Sie hier den Transaktionsmodus.');

define($prefix.'MIN_AMOUNT_TITLE', 'Minimum Betrag');
define($prefix.'MIN_AMOUNT_DESC', 'W&auml;hlen Sie hier den Mindestbetrag <br>(Bitte in EURO-CENT d.h. 5 EUR = 500 Cent).');

define($prefix.'MAX_AMOUNT_TITLE', 'Maximum Betrag');
define($prefix.'MAX_AMOUNT_DESC', 'W&auml;hlen Sie hier den Maximalbetrag <br>(Bitte in EURO-CENT d.h. 5 EUR = 500 Cent).');

define($prefix.'MODULE_MODE_TITLE', 'Modul Mode');
define($prefix.'MODULE_MODE_DESC', 'AFTER: Die Zahldaten werden nachgelagert mit DEBIT Funktion erfasst.<br>NOWPF: Die Zahldaten werden normal im Shop eingegeben und der Kunde wird nach der Bestellung zur Bank weitergeleitet.');

define($prefix.'PAY_MODE_TITLE', 'Payment Mode');
define($prefix.'PAY_MODE_DESC', 'W&auml;hlen Sie zwischen Debit (DB) und Preauthorisation (PA).');

define($prefix.'TEST_ACCOUNT_TITLE', 'Test Account');
define($prefix.'TEST_ACCOUNT_DESC', 'Wenn Transaction Mode nicht LIVE, sollen folgende Accounts (EMail) testen k&ouml;nnen. (Komma getrennt)');

define($prefix.'PROCESSED_STATUS_ID_TITLE', 'Bestellstatus - Erfolgreich');
define($prefix.'PROCESSED_STATUS_ID_DESC', 'Dieser Status wird gesetzt wenn die Bezahlung erfolgreich war.');

define($prefix.'PENDING_STATUS_ID_TITLE', 'Bestellstatus - Wartend');
define($prefix.'PENDING_STATUS_ID_DESC', 'Dieser Status wird gesetzt wenn die der Kunde auf einem Fremdsystem ist.');

define($prefix.'CANCELED_STATUS_ID_TITLE', 'Bestellstatus - Abbruch');
define($prefix.'CANCELED_STATUS_ID_DESC', 'Dieser Status wird gesetzt wenn die Bezahlung abgebrochen wurde.');

define($prefix.'NEWORDER_STATUS_ID_TITLE', 'Bestellstatus - Neue Bestellung');
define($prefix.'NEWORDER_STATUS_ID_DESC', 'Dieser Status wird zu Beginn der Bezahlung gesetzt.');

define($prefix.'STATUS_TITLE', 'Modul aktivieren');
define($prefix.'STATUS_DESC', 'M&ouml;chten Sie das Modul aktivieren?');

define($prefix.'SORT_ORDER_TITLE', 'Anzeigereihenfolge');
define($prefix.'SORT_ORDER_DESC', 'Reihenfolge der Anzeige. Kleinste Ziffer wird zuerst angezeigt.');

define($prefix.'ZONE_TITLE', 'Zahlungszone');
define($prefix.'ZONE_DESC', 'Wenn eine Zone ausgew&auml;hlt ist, gilt die Zahlungsmethode nur f&uuml;r diese Zone.');

define($prefix.'ALLOWED_TITLE', 'Erlaubte Zonen');
define($prefix.'ALLOWED_DESC', 'Geben Sie <b>einzeln</b> die Zonen an, welche f&uuml;r dieses Modul erlaubt sein sollen. (z.B. AT,DE (wenn leer, werden alle Zonen erlaubt))');

define($prefix.'DEBUG_TITLE', 'Debug Modus');
define($prefix.'DEBUG_DESC', 'Schalten Sie diesen nur auf Anweisung von heidelpay an, da sonst eine Bezahlung im Shop nicht mehr funktioniert.');

define($prefix.'TEXT_INFO', '');
define($prefix.'DEBUGTEXT', 'Testsystemmodus: Bitte benutzen Sie keine echten Zahldaten.');

define($prefix.'ACCOUTNUMBER', 'Kontonr. :');
define($prefix.'ACCOUNTBANK', 'Bank :');
define($prefix.'ACCOUTOWNER', 'Inhaber :');
define($prefix.'ACCOUTCOUNTRY', 'Land :');

define($prefix.'FRONTEND_BUTTON_CONTINUE', 'Weiter');
define($prefix.'FRONTEND_BUTTON_CANCEL', 'Vorherige Seite');
