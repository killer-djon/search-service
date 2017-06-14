<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 15.11.16
 * Time: 11:30
 */

namespace RP\SearchBundle\Services\Transformers;

use Common\Core\Facade\Search\QueryFactory\SearchEngine;
use Elastica\SearchableInterface;
use FOS\ElasticaBundle\Elastica\Index;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AbstractTransformer
{
    /**
     * @var Index $elasticaIndex
     */
    protected $elasticaIndex;

    /**
     * Контейнер ядра для получения сервисов
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Сервис поиска
     *
     * @var SearchEngine
     */
    protected $searchService;

    /**
     * Устанавливаем контейнер системный
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Устанавливаем поисковый движок еластика
     *
     * @param SearchableInterface $elasticaIndex
     * @return void
     */
    public function setElasticaIndex(SearchableInterface $elasticaIndex)
    {
        $this->elasticaIndex = $elasticaIndex;
    }

    /**
     * Устанавливаем поискоый сервис
     *
     * @param SearchEngine $engine
     * @return void
     */
    public function setSearchService(SearchEngine $engine)
    {
        $this->searchService = $engine;
    }

    /**
     * @var  string  default delimiter for path()
     */
    public static $delimiter = '.';

    /**
     * Tests if an array is associative or not.
     *
     *     // Returns TRUE
     *     Arr::is_assoc(array('username' => 'john.doe'));
     *
     *     // Returns FALSE
     *     Arr::is_assoc('foo', 'bar');
     *
     * @param   array $array array to check
     * @return  boolean
     */
    public static function is_assoc(array $array)
    {
        // Keys of the array
        $keys = array_keys($array);
        // If the array keys of the keys match the keys, then the array must
        // not be associative (e.g. the keys array looked like {0:0, 1:1...}).
        return array_keys($keys) !== $keys;
    }

    /**
     * Gets a value from an array using a dot separated path.
     *     // Get the value of $array['foo']['bar']
     *     $value = Arr::path($array, 'foo.bar');
     * Using a wildcard "*" will search intermediate arrays and return an array.
     *     // Get the values of "color" in theme
     *     $colors = Arr::path($array, 'theme.*.color');
     *     // Using an array of keys
     *     $colors = Arr::path($array, array('theme', '*', 'color'));
     *
     * @param   array $array array to search
     * @param   mixed $path key path string (delimiter separated) or array of keys
     * @param   mixed $default default value if the path is not set
     * @param   string $delimiter key path delimiter
     * @return  mixed
     */
    public static function path($array, $path, $default = null, $delimiter = null)
    {
        if (!is_array($array)) {
            // This is not an array!
            return $default;
        }
        if (is_array($path)) {
            // The path has already been separated into keys
            $keys = $path;
        } else {
            if (array_key_exists($path, $array)) {
                // No need to do extra processing
                return $array[$path];
            }
            if ($delimiter === null) {
                // Use the default delimiter
                $delimiter = self::$delimiter;
            }
            // Remove starting delimiters and spaces
            $path = ltrim($path, "{$delimiter} ");
            // Remove ending delimiters, spaces, and wildcards
            $path = rtrim($path, "{$delimiter} *");
            // Split the keys by delimiter
            $keys = explode($delimiter, $path);
        }
        do {
            $key = array_shift($keys);
            if (ctype_digit($key)) {
                // Make the key an integer
                $key = (int)$key;
            }
            if (isset($array[$key])) {
                if ($keys) {
                    if (is_array($array[$key])) {
                        // Dig down into the next part of the path
                        $array = $array[$key];
                    } else {
                        // Unable to dig deeper
                        break;
                    }
                } else {
                    // Found the path requested
                    return $array[$key];
                }
            } elseif ($key === '*') {
                // Handle wildcards
                $values = [];
                foreach ($array as $arr) {
                    if ($value = AbstractTransformer::path($arr, implode('.', $keys))) {
                        $values[] = $value;
                    }
                }
                if ($values) {
                    // Found the values requested
                    return $values;
                } else {
                    // Unable to dig deeper
                    break;
                }
            } else {
                // Unable to dig deeper
                break;
            }
        } while ($keys);

        // Unable to find the value requested
        return $default;
    }

    /**
     * Set a value on an array by path.
     *
     * @see Arr::path()
     * @param array $array Array to update
     * @param string $path Path
     * @param mixed $value Value to set
     * @param string $delimiter Path delimiter
     */
    public static function set_path(& $array, $path, $value, $delimiter = null)
    {
        if (!$delimiter) {
            // Use the default delimiter
            $delimiter = AbstractTransformer::$delimiter;
        }
        // The path has already been separated into keys
        $keys = $path;
        if (!is_array($path)) {
            // Split the keys by delimiter
            $keys = explode($delimiter, $path);
        }
        // Set current $array to inner-most array path
        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (ctype_digit($key)) {
                // Make the key an integer
                $key = (int)$key;
            }
            if (!isset($array[$key])) {
                $array[$key] = [];
            }
            $array = &$array[$key];
        }
        // Set key on inner-most array
        $array[array_shift($keys)] = $value;
    }

    /**
     * Формирование сложных/длинных названий полей
     * консистентных
     *
     * @param array $fields Набор полей для формирования
     * @param string $delimiter Разделитель
     * @return string
     */
    public static function createCompleteKey(array $fields, $delimiter = null)
    {
        if (!$delimiter) {
            // Use the default delimiter
            $delimiter = AbstractTransformer::$delimiter;
        }

        return implode($delimiter, $fields);
    }

    /**
     * Convert a flat array with parent ID's to a nested tree
     *
     * @link http://blog.tekerson.com/2009/03/03/converting-a-flat-array-with-parent-ids-to-a-nested-tree/
     * @param array $flat Flat array
     * @param string $idField Key name for the element containing the item ID
     * @param string $parentIdField Key name for the element containing the parent item ID
     * @param string $childNodesField Key name for the element for placement children
     * @return array
     */
    public static function convertToTree(
        array $flat,
        $idField = 'id',
        $parentIdField = 'parentId',
        $childNodesField = 'children'
    ) {
        $flat = array_merge([
            [
                $idField => 1,
                $parentIdField => 0,
                'name' => 'Root',
            ],
        ], $flat);
        $indexed = [];


        // first pass - get the array indexed by the primary id
        foreach ($flat as $row) {
            $indexed[$row[$idField]] = $row;
            $indexed[$row[$idField]][$childNodesField] = [];
        }

        //second pass
        $root = null;
        foreach ($indexed as $id => $row) {
            $indexed[$row[$parentIdField]][$childNodesField][] =& $indexed[$id];
            if (!$row[$parentIdField]) {
                $root = $id;
            }
        }

        return $indexed[$root][$childNodesField];
    }

    /**
     * Очищаем объекты от пустых массивов
     * !!! необходиом для быдло андройда иначе там косяк с парсингом JSON объекта
     *
     * @param array|null $haystack набор данных
     * @return array Очищенный массив данных
     */
    public static function array_filter_recursive($haystack)
    {
        return AbstractTransformer::array_walk_recursive_delete($haystack, function ($value) {
            if (is_array($value)) {
                return empty($value);
            }
            return ($value == null);
        });

    }

    /**
     * Remove any elements where the callback returns true
     *
     * @param  array $array the array to walk
     * @param  callable $callback callback takes ($value, $key, $userdata)
     * @param  mixed $userdata additional data passed to the callback.
     * @return array
     */
    public static function array_walk_recursive_delete(array &$array, callable $callback, $userdata = null)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $value = AbstractTransformer::array_walk_recursive_delete($value, $callback, $userdata);
            }
            if ($callback($value, $key, $userdata)) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * Метод опять для быдло андройда который
     * в avatar комментов вкладывает mediumAvatar ключ path
     */
    public static function recursiveTransformAvatar(& $itemObject)
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