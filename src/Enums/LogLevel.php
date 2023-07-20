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

    public static function getForRender(): array
    {
        return [
            [
                'label' => __('Erro'),
                'value' => self::ERROR
            ],
            [
                'label' => __('Informativo'),
                'value' => self::INFO
            ],
            [
                'label' => __('Alerta'),
                'value' => self::ALERT
            ],
            [
                'label' => __('Crítico'),
                'value' => self::CRITICAL
            ],
        ];
    }

    public static function getTranslation(string $type): ?string
    {
        switch ($type) {
            case self::ERROR:
                return __('Erro');
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

    public static function getClass(string $type): ?string
    {
        switch ($type) {
            case self::CRITICAL:
            case self::EMERGENCY:
            case self::ERROR:
                return 'chip--red';
            case self::ALERT:
            case self::WARNING:
                return 'chip--yellow';
            case self::NOTICE:
            case self::INFO:
                return 'chip--blue';
            case self::DEBUG:
                return 'chip--neutral';
        }

        return $type;
    }
}
