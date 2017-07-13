<?php
/**
 * Эмулятор kernel контейнера для тестов
 * сервисов системы
 *
 */
namespace RP\SearchBundle\Tests\DependencyInjection;

use Symfony\Component\DependencyInjection\Container;

class MockerContainer extends Container
{
    /**
     * @var object[] $stubs
     */
    private $stubs = array();

    public function stub($serviceId, $stub)
    {
        if (!$this->has($serviceId)) {
            throw new \InvalidArgumentException(sprintf('Cannot stub a non-existent service: "%s"', $serviceId));
        }

        $this->stubs[$serviceId] = $stub;
    }

    /**
     * @param string  $id
     * @param integer $invalidBehavior
     *
     * @return object
     */
    public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE)
    {
        if (array_key_exists($id, $this->stubs)) {
            return $this->stubs[$id];
        }

        return parent::get($id, $invalidBehavior);
    }

    /**
     * @param string $id
     *
     * @return boolean
     */
    public function has($id)
    {
        if (array_key_exists($id, $this->stubs)) {
            return true;
        }

        return parent::has($id);
    }
}