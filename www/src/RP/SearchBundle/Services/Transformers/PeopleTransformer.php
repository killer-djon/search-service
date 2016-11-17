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
                AbstractTransformer::recursiveTransformAvatar($obj);

                if (!is_null($subContext)) {
                    $result[] = [$subContext => $obj];
                } else {
                    $result[] = $obj;
                }
            }
        }

        return AbstractTransformer::array_filter_recursive($result);
    }


}