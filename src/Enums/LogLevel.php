<?php

namespace Moloni\Enums;

class LogLevel
{
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';

    public static function getTranslation(string $type): ?string
    {
        switch ($type) {
            case self::ERROR:
                return __('Error');
            case self::WARNING:
                return __('Aviso');
            case self::INFO:
                return __('Informativo');
            case self::DEBUG:
                return __('Debug');
            case self::ALERT:
                return __('Alerta');
            case self::CRITICAL:
                return __('Crítico');
            case self::EMERGENCY:
                return __('Emergência');
            case self::NOTICE:
                return __('Observação');
        }

        return $type;
    }
}