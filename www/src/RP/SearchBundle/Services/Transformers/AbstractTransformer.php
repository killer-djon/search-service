<?php
/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 15.11.16
 * Time: 11:30
 */

namespace RP\SearchBundle\Services\Transformers;

use Common\Core\Facade\Search\QueryFactory\SearchEngine;
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
     * @param Index $elasticaIndex
     * @return void
     */
    public function setElasticaIndex(Index $elasticaIndex)
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
     * Gets a value from an array using a dot separated path.
     *
     *     // Get the value of $array['foo']['bar']
     *     $value = Arr::path($array, 'foo.bar');
     *
     * Using a wildcard "*" will search intermediate arrays and return an array.
     *
     *     // Get the values of "color" in theme
     *     $colors = Arr::path($array, 'theme.*.color');
     *
     *     // Using an array of keys
     *     $colors = Arr::path($array, array('theme', '*', 'color'));
     *
     * @param   array   $array      array to search
     * @param   mixed   $path       key path string (delimiter separated) or array of keys
     * @param   mixed   $default    default value if the path is not set
     * @param   string  $delimiter  key path delimiter
     * @return  mixed
     */
    public static function path($array, $path, $default = NULL, $delimiter = NULL)
    {
        if ( ! is_array($array))
        {
            // This is not an array!
            return $default;
        }
        if (is_array($path))
        {
            // The path has already been separated into keys
            $keys = $path;
        }
        else
        {
            if (array_key_exists($path, $array))
            {
                // No need to do extra processing
                return $array[$path];
            }
            if ($delimiter === NULL)
            {
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
        do
        {
            $key = array_shift($keys);
            if (ctype_digit($key))
            {
                // Make the key an integer
                $key = (int) $key;
            }
            if (isset($array[$key]))
            {
                if ($keys)
                {
                    if (is_array($array[$key]))
                    {
                        // Dig down into the next part of the path
                        $array = $array[$key];
                    }
                    else
                    {
                        // Unable to dig deeper
                        break;
                    }
                }
                else
                {
                    // Found the path requested
                    return $array[$key];
                }
            }
            elseif ($key === '*')
            {
                // Handle wildcards
                $values = array();
                foreach ($array as $arr)
                {
                    if ($value = AbstractTransformer::path($arr, implode('.', $keys)))
                    {
                        $values[] = $value;
                    }
                }
                if ($values)
                {
                    // Found the values requested
                    return $values;
                }
                else
                {
                    // Unable to dig deeper
                    break;
                }
            }
            else
            {
                // Unable to dig deeper
                break;
            }
        }
        while ($keys);
        // Unable to find the value requested
        return $default;
    }
}