<?php

namespace App\Http\Requests;

use App\Rules\OdId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
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
            'od_id' => ['bail', 'string', 'filled'],
            'description' => ['bail', 'string', 'filled'],
        ];
    }

    /**
     * Finds and sets a unique OD ID and name for the given object, ensuring that it is unique with respect to
     * its parent scope
     *
     * @param ConversationObject $object
     * @param ConversationObject|null $parent
     * @param Request|null $request
     * @param bool $isIntent
     * @param bool $isTemplate
     * @return ConversationObject
     */
    public static function setUniqueOdId(
        ConversationObject $object,
        Request $request,
        ?ConversationObject $parent = null,
        bool $isIntent = false,
        bool $isTemplate = false
    ): ConversationObject {
        $originalOdId = $object->getOdId();
        $odId = $request->get('od_id', self::formatId($originalOdId, null, $isIntent, $isTemplate));

        $i = 1;
        while (!OdId::isOdIdUniqueWithinParentScope($odId, $parent)) {
            $i++;
            $odId = self::formatId($originalOdId, $i, $isIntent);
        }

        if ($i > 1) {
            $name = $request->get('name', self::formatName($object->getName(), $i, $isIntent, $isTemplate));
        } else {
            $name = $request->get('name', self::formatName($object->getName(), null, $isIntent, $isTemplate));
        }

        $object->setOdId($odId);
        $object->setName($name);

        return $object;
    }

    /**
     * @param ConversationObject $object
     * @param Request $request
     * @return ConversationObject
     */
    public static function setDescription(ConversationObject $object, Request $request): ConversationObject
    {
        $description = $request->get('description', $object->getDescription());
        $object->setDescription($description);

        return $object;
    }

    /**
     * @param string $id
     * @param int|null $number
     * @param bool $isIntent
     * @param bool $isTemplate
     * @return string
     */
    public static function formatId(string $id, int $number = null, bool $isIntent = false, bool $isTemplate = false): string
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
    public static function formatName(string $name, int $number = null, bool $isIntent = false, bool $isTemplate = false): string
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
