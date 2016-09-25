<?php

namespace Common\Core\Exceptions\Domain;

/**
 * Исключение в работе репозитория домена
 */
class DomainRepositoryException extends \Exception
{
	/**
	 * Ошибка чтения данных
	 *
	 * @const string 	READ_ERROR
	 */
    const READ_ERROR   = 'Data reading error';
    
    /**
	 * Ошибка сохранения
	 *
	 * @const string 	SAVE_ERROR
	 */
    const SAVE_ERROR   = 'Data saving error';
    
    /**
	 * Ошибка удаления данных
	 *
	 * @const string 	DELETE_ERROR
	 */
    const DELETE_ERROR = 'Data deleting error';
}
