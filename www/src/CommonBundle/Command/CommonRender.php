<?php
namespace CommonBundle\Command;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Symfony\Component\Filesystem\Filesystem;

class CommonRender extends Generator
{

    /**
     * Генерируем тело класса бандла
     * учитывая наш новый template
     *
     * @param string $template Файл шаблона twig
     * @param string $target Путь к файлу бандла для вставки шаблона
     * @param array  $parameters Массив параметров для генерации шаблона
     *
     * @return int Количество записанных байт в файл
     */
    public function renderBodyBundle($template, $target, $parameters)
    {
        /** Render file bundle from parent class */
        $this->setSkeletonDirs(__DIR__);
        $this->clearBundleFileContent($target);

        return $this->renderFile($template, $target, $parameters);
    }

    private function clearBundleFileContent($target)
    {
        if( file_exists($target) )
        {
            return file_put_contents($target, "");
        }
    }
}