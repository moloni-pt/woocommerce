<?php

namespace Moloni;

class Notice
{

    const NOTICE_FIELD = '_moloni_admin_notice_messages';

    /**
     * Throw all message
     */
    public static function showMessages()
    {
        $messages = get_option(self::NOTICE_FIELD);

        if (!empty($messages) && isset($messages['message'])) {
            if ($messages['notice-level'] == 'custom') {
                echo html_entity_decode($messages['message']);
            } else {
                echo self::getMessageHtml($messages['message'], $messages['notice-level']);
            }
        }

        delete_option(self::NOTICE_FIELD);
    }

    public static function addMessageCustom($message)
    {
        self::addMessage($message, 'custom');
    }

    public static function addMessageInfo($message)
    {
        self::addMessage($message, 'info');
    }

    public static function addMessageSuccess($message)
    {
        self::addMessage($message, 'success');
    }

    public static function addMessageWarning($message)
    {
        self::addMessage($message, 'warning');
    }

    public static function addMessageError($message)
    {
        self::addMessage($message, 'error');
    }

    /**
     * @param $message string
     * @param string $type
     */
    private static function addMessage($message, $type = 'error')
    {
        $messages = [
            'message' => $message,
            'notice-level' => $type
        ];

        update_option(self::NOTICE_FIELD, $messages);
    }


    /**
     * @param $message
     * @param string $type
     * @return string
     */
    private static function getMessageHtml($message, $type = 'error')
    {
        $template = "<div class=\"notice is-dismissible notice-" . $type . "\">";
        $template .= "<p>" . $message . "</p>";
        $template .= "</div>";

        return $template;
    }

}
