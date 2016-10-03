<?php
namespace CommonBundle\Command;

use Sensio\Bundle\GeneratorBundle\Manipulator\ConfigurationManipulator;
use Sensio\Bundle\GeneratorBundle\Model\Bundle;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\HttpKernel\KernelInterface;
use Sensio\Bundle\GeneratorBundle\Generator\BundleGenerator;
use Sensio\Bundle\GeneratorBundle\Manipulator\KernelManipulator;
use Sensio\Bundle\GeneratorBundle\Manipulator\RoutingManipulator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Sensio\Bundle\GeneratorBundle\Command\GenerateBundleCommand;

class GenerateCommonBundleCommand extends GenerateBundleCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('common:generate:bundle');
    }

    /**
     * Практически одинаковая команда с родителем
     * за исключением того что мы еще меняет тело класса бандла
     *
     * @see Command
     *
     * @throws \InvalidArgumentException When namespace doesn't end with Bundle
     * @throws \RuntimeException         When bundle can't be executed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** Полная генерация бандла и все что необходимо */
        parent::execute($input, $output);

        /** Далее custom генерация содержимого файла класса бандла */
        /** @var  Bundle $bundle */
        $bundle = $this->createBundleObject($input);

        /** @var  string Target direcotry of the generated bundle */
        $dir = $bundle->getTargetDirectory();

        /** @var BundleGenerator $generator */
        $generator = new CommonRender($this->getContainer()->get('filesystem'));

        /** @var  array $parameters */
        $parameters = array(
            'namespace' => $bundle->getNamespace(),
            'bundle' => $bundle->getName(),
            'format' => $bundle->getConfigurationFormat(),
            'bundle_basename' => $bundle->getBasename(),
            'extension_alias' => $bundle->getExtensionAlias(),
        );

        $generator->renderBodyBundle('bundle/Bundle.php.twig', $dir.'/'.$bundle->getName().'.php', $parameters);
    }
}