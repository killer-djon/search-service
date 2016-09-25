<?php

namespace RP\SearchBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Common\Core\DependencyInjection\Compiler\CustomConfigCompilerPass;

class RPSearchBundle extends Bundle
{
	public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new CustomConfigCompilerPass(__DIR__));
    }
}
