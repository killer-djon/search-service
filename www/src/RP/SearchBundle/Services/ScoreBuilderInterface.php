<?php
/**
 * Интерфейс постройщика запросов с кастомным расчетом очков релевантности поиска
 */
namespace RP\SearchBundle\Services;

interface ScoreBuilderInterface
{
    /**
     * Возвращает текст скрипта расчета очков релевантности результатов поиска
     *
     * @abstract
     * @return string
     */
    public function getScript();
}