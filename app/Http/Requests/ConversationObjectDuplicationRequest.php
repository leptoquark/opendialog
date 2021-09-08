<?php

namespace App\Http\Requests;

use App\Rules\OdId;
use Illuminate\Foundation\Http\FormRequest;
use OpenDialogAi\Core\Conversation\ConversationObject;

class ConversationObjectDuplicationRequest extends FormRequest
{
    use ConversationObjectRequestTrait;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return $this->odIdRule() + [
            'name' => ['bail', 'string', 'filled'],
            'od_id' => ['bail', 'string', 'filled']
        ];
    }

    /**
     * Finds and sets a unique OD ID and name for the given object, ensuring that it is unique with respect to
     * it's parent scope
     *
     * @param ConversationObject $object
     * @param ConversationObject|null $parent
     * @param bool $isIntent
     * @param bool $isTemplate
     * @return ConversationObject
     */
    public function setUniqueOdId(
        ConversationObject $object,
        ?ConversationObject $parent = null,
        bool $isIntent = false,
        bool $isTemplate = false
    ): ConversationObject {
        $originalOdId = $object->getOdId();
        $odId = $this->get('od_id', $this->formatId($originalOdId, null, $isIntent, $isTemplate));

        $i = 1;
        while (!OdId::isOdIdUniqueWithinParentScope($odId, $parent)) {
            $i++;
            $odId = $this->formatId($originalOdId, $i, $isIntent);
        }

        if ($i > 1) {
            $name = $this->get('name', $this->formatName($object->getName(), $i, $isIntent, $isTemplate));
        } else {
            $name = $this->get('name', $this->formatName($object->getName(), null, $isIntent, $isTemplate));
        }

        $object->setOdId($odId);
        $object->setName($name);

        return $object;
    }

    /**
     * @param string $id
     * @param int|null $number
     * @param bool $isIntent
     * @param bool $isTemplate
     * @return string
     */
    public function formatId(string $id, int $number = null, bool $isIntent = false, bool $isTemplate = false): string
    {
        if (is_null($number)) {
            if ($isIntent) {
                $id = sprintf("%s%s", $id, $isTemplate ? '' : 'Copy');
            } else {
                $id = sprintf("%s%s", $id, $isTemplate ? '' : '_copy');
            }
        } else {
            if ($isIntent) {
                $id = sprintf("%s%s%d", $id, $isTemplate ? '' : 'Copy', $number);
            } else {
                $id = sprintf("%s%s_%d", $id, $isTemplate ? '' : '_copy', $number);
            }
        }

        return $id;
    }

    /**
     * @param string $name
     * @param int|null $number
     * @param bool $isIntent
     * @param bool $isTemplate
     * @return string
     */
    public function formatName(string $name, int $number = null, bool $isIntent = false, bool $isTemplate = false): string
    {
        if ($isIntent) {
            $name = sprintf("%s%s", $name, $isTemplate ? '' : 'Copy');
        } else {
            $name = sprintf("%s%s", $name, $isTemplate ? '' : ' copy');
        }

        if (!is_null($number)) {
            if ($isIntent) {
                $name = sprintf("%s%d", $name, $number);
            } else {
                $name = sprintf("%s %d", $name, $number);
            }
        }

        return $name;
    }
}
