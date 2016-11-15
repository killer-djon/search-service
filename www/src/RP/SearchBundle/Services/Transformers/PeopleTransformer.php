<?php
namespace RP\SearchBundle\Services\Transformers;

use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;

class PeopleTransformer extends AbstractTransformer implements TransformerInterface
{

    /**
     * Трансформируем данные в соответсвии с заданным маппингом полей
     *
     * @param array $dataResult Набор данных для преобразования
     * @param string $context Контекст массива (т.е. ключ ассоц.массива)
     * @param string $subContext Это если есть вложенность, нам нужен ключ вложенного объекта
     * @return array
     */
    public function transform(array $dataResult, $context, $subContext = null)
    {
        $result = [];
        if (!is_null($dataResult[$context]) && isset($dataResult[$context])) {
            foreach ($dataResult[$context] as $key => $itemObject) {
                $obj = (!is_null($subContext) ? $itemObject[$subContext] : $itemObject);
                $this->recursiveTransformAvatar($obj);

                if (!is_null($subContext)) {
                    $result[] = [$subContext => $obj];
                } else {
                    $result[] = $obj;
                }
            }
        }

        return $result;
    }

    private function recursiveTransformAvatar(& $itemObject)
    {
        $avatarItems = AbstractTransformer::path($itemObject, 'avatar.comments.items');
        if (!is_null($avatarItems) && !empty($avatarItems)) {
            foreach ($avatarItems as $key => & $avatar) {
                AbstractTransformer::set_path($avatar, 'author.avatar.mediumAvatar', [
                    'path' => AbstractTransformer::path($avatar, 'author.avatar.mediumAvatar'),
                ]);
            }

            AbstractTransformer::set_path($itemObject, 'avatar.comments.items', $avatarItems);
        }
    }
}