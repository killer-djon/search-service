<?php
namespace Common\Core\Loader;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\RecursiveValidator as LegacyValidatorInterface;
use Common\Core\Facade\Loader\ModelLoaderInterface;

class JSONModelLoader implements ModelLoaderInterface
{
    /** @var Validator */
    private $validator;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param Validator $validator
     * @param TranslatorInterface $translator
     */
    public function __construct(LegacyValidatorInterface $validator, TranslatorInterface $translator)
    {
        $this->validator = $validator;
        $this->translator = $translator;
    }

    /**
     * @param $object
     * @param Request $request
     * @return void
     */
    public function load($object, Request $request)
    {
        $dataModel = array_merge($request->request->all(), $request->files->all());

        foreach($dataModel as $attribute => $value) {

            // try to refuse of using reflection, because it's really slow
            $setter = $this->buildSetterName($attribute);
            if (!is_callable([$object, $setter])) {
                continue;
            }

            $object->$setter($value);
        }
    }

    /**
     * Заполняет и проверяет модель из реквеста.
     * Возвращает либо null, в лучае отсутсвия ошибок, либо строку ошибки для отправки на клиент.
     *
     * @param object $formModel
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return null|string
     */
    public function loadAndValidateModel($formModel, Request $request)
    {
        $this->load($formModel, $request);
        $list = $this->validator->validate($formModel);
        if ($list->count()) {
            return $this->getPrepareErrors($list);
        }
        return null;
    }

    /**
     * Форматирует список ошибок.
     *
     * @param ConstraintViolationList $list
     * @return string
     */
    private function getPrepareErrors(ConstraintViolationList $list)
    {
        $errors =  [];
        /** @var ConstraintViolation $listItem */

        foreach ($list as $listItem) {
	        $errors[$listItem->getPropertyPath()] = $this->translator->trans(
            	$listItem->getMessageTemplate(), 
            	$listItem->getParameters(), 
            	'validators'
            );
        }

        return $errors;
    }

    private function buildSetterName($attributeName)
    {
        return 'set' . ucfirst($attributeName);
    }
    
}
