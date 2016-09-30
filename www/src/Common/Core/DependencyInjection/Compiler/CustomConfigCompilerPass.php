<?php
namespace Common\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Yaml\Yaml;

/**
 * Класс который склеивает разные custom конфиги
 * c конфигами оригинальных бандлов
 * данное решение необходимо для того, чтобы мы могли иметь глобальные конфиги
 * и локальные в бандлах - это для микросервисной архитектуры
 */
class CustomConfigCompilerPass implements CompilerPassInterface
{
	/**
	 * @access private
	 * @var string $path Путь к бандлу откуда отсчитываем путь к конфигам
	 */
	private $_path;

    /**
     * @access private
     * @var string $extensionName Имя расширения бандла
     */
    private $extensionName;
	
	/**
	 * Путь до компилера конфигурации
	 * необходимо указать из вызываемого бандла	
	 * как: new CustomConfigCompilerPass(путь к бандлу)
	 *
	 * @param string $path Путь к бандлу откуда отсчитываем путь к конфигам
     * @param string $extensionKeyName Обязательно имя расширения (бандла) для custom конфигов
	 */
	public function __construct($path = null, $extensionKeyName)
	{
		$this->_path = $path;
        $this->extensionName = $extensionKeyName;
	}
	
	
    /**
     * Склеивание конфигов разных бандлов
     * перед началом работы с RabbitMQ
     * мы в глобальном конфиге прописываем только connection config
     * далее в нашем бандле (который будет работать с очередью) прописывам конфиги публикаторов
     * это необходимо для удобства управления конфигурациями
     * затем мы вписываем кусочек нашего конфига в конфиг реального бандла
     *
     * Индексы обоих массивов при склейки должны быть целочисленные
     * т.е.
     *
     *  [0] => array(
     *      ...
     *      'connections'
     *      ...
     *  )
     *
     * [1] => array(
     *      ...
     *      'producers'
     *      ...
     *  )
     *
     * Только таким образом удасться склеить несколько конфиг файлов в бандле
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
	    if( is_null($this->_path) || !file_exists($this->_path . '/Resources/config/custom') )
	    	return;
	 
	    $customConfigPath = new \DirectoryIterator( $this->_path . '/Resources/config/custom' );   
        foreach($customConfigPath as $key => $configFile)
        {
            if(!$configFile->isDot() && $configFile->isFile() && $configFile->getExtension() == 'yml')
            {
                $config = Yaml::parse(file_get_contents( $configFile->getPathname() ));
                $extensionName = key($config[$this->extensionName]);

                if(!empty($extensionName))
                {
                    $extension = $container->getExtension($extensionName);

                    $extensionConfig = $container->getExtensionConfig($extension->getAlias());
                    $resultConfig = array_replace_recursive(current($extensionConfig), $config[$this->extensionName][$extensionName]);

                    $extension->load([$resultConfig], $container);
                }
            }
        }
    }
}
