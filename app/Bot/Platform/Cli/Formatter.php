<?php


namespace App\Bot\Platform\Cli;


use OpenDialogAi\Core\Components\ODComponentTypes;
use OpenDialogAi\ResponseEngine\Formatters\BaseMessageFormatter;
use OpenDialogAi\ResponseEngine\Message\AutocompleteMessage;
use OpenDialogAi\ResponseEngine\Message\ButtonMessage;
use OpenDialogAi\ResponseEngine\Message\DatePickerMessage;
use OpenDialogAi\ResponseEngine\Message\EmptyMessage;
use OpenDialogAi\ResponseEngine\Message\FormMessage;
use OpenDialogAi\ResponseEngine\Message\FullPageFormMessage;
use OpenDialogAi\ResponseEngine\Message\FullPageRichMessage;
use OpenDialogAi\ResponseEngine\Message\HandToSystemMessage;
use OpenDialogAi\ResponseEngine\Message\ImageMessage;
use OpenDialogAi\ResponseEngine\Message\ListMessage;
use OpenDialogAi\ResponseEngine\Message\LongTextMessage;
use OpenDialogAi\ResponseEngine\Message\MetaMessage;
use OpenDialogAi\ResponseEngine\Message\OpenDialogMessage;
use OpenDialogAi\ResponseEngine\Message\OpenDialogMessages;
use OpenDialogAi\ResponseEngine\Message\RichMessage;

class Formatter extends BaseMessageFormatter
{
    protected static string $componentId = 'formatter.core.cli';
    protected static string $componentSource = ODComponentTypes::CORE_COMPONENT_SOURCE;

    public function getMessages(string $markup): OpenDialogMessages
    {
        return new CliMessages($markup);
    }

    public function generateAutocompleteMessage(array $template): AutocompleteMessage
    {
        // TODO: Implement generateAutocompleteMessage() method.
    }

    public function generateButtonMessage(array $template): ButtonMessage
    {
        // TODO: Implement generateButtonMessage() method.
    }

    public function generateEmptyMessage(): EmptyMessage
    {
        // TODO: Implement generateEmptyMessage() method.
    }

    public function generateFormMessage(array $template): FormMessage
    {
        // TODO: Implement generateFormMessage() method.
    }

    public function generateFullPageFormMessage(array $template): FullPageFormMessage
    {
        // TODO: Implement generateFullPageFormMessage() method.
    }

    public function generateImageMessage(array $template): ImageMessage
    {
        // TODO: Implement generateImageMessage() method.
    }

    public function generateListMessage(array $template): ListMessage
    {
        // TODO: Implement generateListMessage() method.
    }

    public function generateMetaMessage(array $template): MetaMessage
    {
        // TODO: Implement generateMetaMessage() method.
    }

    public function generateLongTextMessage(array $template): LongTextMessage
    {
        // TODO: Implement generateLongTextMessage() method.
    }

    public function generateRichMessage(array $template): RichMessage
    {
        // TODO: Implement generateRichMessage() method.
    }

    public function generateFullPageRichMessage(array $template): FullPageRichMessage
    {
        // TODO: Implement generateFullPageRichMessage() method.
    }

    public function generateTextMessage(array $template): OpenDialogMessage
    {
        // TODO: Implement generateTextMessage() method.
    }

    public function generateHandToSystemMessage(array $template): HandToSystemMessage
    {
        // TODO: Implement generateHandToSystemMessage() method.
    }

    public function generateDatePickerMessage(array $template): DatePickerMessage
    {
        // TODO: Implement generateDatePickerMessage() method.
    }
}