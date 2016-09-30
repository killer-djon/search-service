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

class GenerateUserCommand extends GenerateBundleCommand
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

    }
}