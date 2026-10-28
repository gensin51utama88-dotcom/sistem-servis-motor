<?php
/** Token Fonnte hanya dibaca dari environment variable FONNTE_TOKEN. */
if (!defined('SERVISKU_FONNTE_TOKEN')) {
    define('SERVISKU_FONNTE_TOKEN', getenv('FONNTE_TOKEN') ?: '');
}
