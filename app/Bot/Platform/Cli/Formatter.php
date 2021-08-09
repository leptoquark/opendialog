<?php


namespace App\Bot\Platform\Cli;


use App\Bot\Platform\Cli\Messages\CliTextMessage;
use DOMDocument;
use Illuminate\Support\Facades\Log;
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
use SimpleXMLElement;

class Formatter extends BaseMessageFormatter
{
    protected static string $componentId = 'formatter.core.cli';
    protected static string $componentSource = ODComponentTypes::CORE_COMPONENT_SOURCE;

    public function getMessages(string $markup): OpenDialogMessages
    {
        $messages = [];

        try {
            $message = new SimpleXMLElement($markup);

            foreach ($message->children() as $item) {
                // Deal with attribute message
                $messages[] = $this->parseMessage($item);
            }

            // Disable text and hide avatar don't make sense outside of webchat
        } catch (\Exception $e) {
            Log::warning(sprintf('Message Builder error: %s', $e->getMessage()));
            return new CliMessages();
        }

        $messageWrapper = new CliMessages();
        foreach ($messages as $message) {
            $messageWrapper->addMessage($message);
        }

        return $messageWrapper;
    }

    /**
     * @param SimpleXMLElement $message
     * @return OpenDialogMessage
     */
    private function parseMessage(SimpleXMLElement $message): OpenDialogMessage
    {
        switch ($message->getName()) {
            case self::TEXT_MESSAGE:
                $text = $this->getMessageText($message);
                $template = [self::TEXT => $text];
                return $this->generateTextMessage($template);
        }
    }

    protected function getMessageText(SimpleXMLElement $element): string
    {
        $dom = new DOMDocument();
        $dom->loadXML($element->asXml());

        $text = '';
        foreach ($dom->childNodes as $node) {
            foreach ($node->childNodes as $item) {
                if ($item->nodeType === XML_TEXT_NODE) {
                    if (!empty(trim($item->textContent))) {
                        $text .= ' ' . trim($item->textContent);
                    }
                } elseif ($item->nodeType === XML_ELEMENT_NODE) {
                    if ($item->nodeName === self::LINK) {
                        $openNewTab = $this->convertToBoolean((string)$item->getAttribute('new_tab'));

                        $link = [
                            self::OPEN_NEW_TAB => $openNewTab,
                            self::TEXT => '',
                            self::URL => '',
                        ];

                        foreach ($item->childNodes as $t) {
                            $link[$t->nodeName] = trim($t->nodeValue);
                        }

                        if ($link[self::URL]) {
                            $text .= ' ' . $this->generateLinkHtml(
                                    $link[self::URL],
                                    $link[self::TEXT],
                                    $link[self::OPEN_NEW_TAB]
                                );
                        } else {
                            Log::debug('Not adding link to message text, url is empty');
                        }
                    }
                }
            }
        }

        return trim($text);
    }

    /**
     * @param string $value
     * @return bool
     */
    private function convertToBoolean(string $value): bool
    {
        if ($value === '1' || $value === 'true') {
            return true;
        }
        return false;
    }

    protected function generateLinkHtml(string $url, string $text, bool $openNewTab): string
    {
        return $url;
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
        return (new CliTextMessage())->setText($template[self::TEXT]);
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