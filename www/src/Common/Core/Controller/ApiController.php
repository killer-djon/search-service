<?php
/**
 * Main API controller
 * this controller contains all pre-defined methods
 * which help to you in the futures
 */
namespace Common\Core\Controller;

use Common\Core\Constants\RequestConstant;
use Common\Core\Facade\Search\QueryFactory\SearchServiceInterface;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Common\Core\Exceptions\ResponseFormatException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Common\Core\Serializer\XMLWrapper;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Основной абстрактный класс контроллера
 */
abstract class ApiController extends FOSRestController
{
    use ControllerTrait;

    /**
     * Разделитель фильтра маркеров
     *
     * @var string MARKER_FILTER_DELIMITER
     */
    const MARKER_FILTER_DELIMITER = ';';

    /**
     * Атрибут текст ошибки
     *
     * @const string TEXT
     */
    const TEXT = 'text';

    /**
     * Атрибут транслита текста ошибки
     *
     * @const string TEXT_TRANSLIT
     */
    const TEXT_TRANSLIT = 'text_translate';

    /**
     * Атрибут в котором возвращается ответ клиенту
     *
     * @const string RESULT
     */
    const RESULT = 'result';

    /**
     * Параметр данных об ошибке
     *
     * @const string ERROR
     */
    const ERROR = 'error';

    /**
     * Параметр кода ответа
     *
     * @const string RESPONSE_CODE
     */
    const ERROR_CODE = 'errorCode';

    /**
     * ID пользователя осуществившего запрос к API
     *
     * @var string $requestUserId
     */
    private $requestUserId;

    /**
     * ID города в запросе по городу
     *
     * @var string $requestCityId
     */
    private $requestCityId;

    /**
     * Объект запроса
     *
     * @var \Symfony\Component\HttpFoundation\Request $_request
     */
    private $_request;

    /**
     * При необходимоси в реопределенном методе можем
     * заранее устаналивать нужные нам сервисы для удобства дальнейшей работы
     *
     * @override
     * @param null|\Symfony\Component\DependencyInjection\ContainerInterface $container
     * @return void|\Symfony\Component\HttpFoundation\Response
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);

        $this->setCurrentRequest($this->get('request_stack')->getCurrentRequest());
        $this->setRequestFormat();
        $this->setTemplateAction();
    }

    /**
     * Устанавливаем в свойство класса полученный объект запроса
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    protected function setCurrentRequest(Request $request)
    {
        /** @var ID пользователя */
        $this->requestUserId = $request->get(RequestConstant::USER_ID_PARAM, RequestConstant::NULLED_PARAMS);
        $this->requestCityId = $request->get(RequestConstant::CITY_SEARCH_PARAM, RequestConstant::NULLED_PARAMS);
        $this->_request = $request;

        $this->parseSkipCountRequest($request);
        $this->parseGeoPointRequest($request);
    }

    /**
     * Парсим приходящий набор фильтров
     * он может быть передан в строке разными разделителями
     * допустимые разделители: , : ; - пробел
     *
     * @param mixed $filterTypes
     * @return array Набор фильтров
     */
    public function getParseFilters($filterTypes)
    {
        if (is_array($filterTypes) && count($filterTypes)) {
            return $filterTypes;
        }

        /** разделяем фильтры по разделителю (разделитель может быть любым из спец.символов) */
        $types = preg_replace('/[^a-zA-Z0-9]+/', self::MARKER_FILTER_DELIMITER, $filterTypes);

        $filtered = array_filter(explode(self::MARKER_FILTER_DELIMITER, strtolower($types)), function ($type) {
            return !empty($type);
        });

        return array_values($filtered);
    }

    /**
     * Полуачем ID пользователя
     * который сделал запрос к API
     *
     * @return string $requestUserId
     */
    public function getRequestUserId()
    {
        if (is_null($this->requestUserId)) {
            return $this->_handleViewWithError(
                new BadRequestHttpException(
                    'Не указан userId пользователя',
                    null,
                    Response::HTTP_BAD_REQUEST
                )
            );
        }

        return $this->requestUserId;
    }

    /**
     * Полуачем ID города при запросе к API
     *
     * @return string $requestCityId
     */
    public function getRequestCityId()
    {
        if (is_null($this->requestCityId)) {
            return $this->_handleViewWithError(
                new BadRequestHttpException(
                    'Не задан город для поиска по городу',
                    null,
                    Response::HTTP_BAD_REQUEST
                )
            );
        }

        return $this->requestCityId;
    }

    /**
     * Устанавливает в свойство класса текущий формат запроса клиента
     * формат может быть xml|json|html
     * В зависимости от формата мы будем строить формат ответа
     *
     * @return void
     */
    private function setRequestFormat()
    {
        $this->_requestFormat = $this->_request->get('_format');
    }

    /**
     * Устанавливает в свойство класса шаблон
     * который будет выдан в случае запроса формата html
     *
     * @return void
     */
    protected function setTemplateAction()
    {
        $this->_template = $this->_request->get('template');
    }

    /**
     * Оборачиваем данные в XMLWrapper, задаем их во View и возвращаем объект Response
     *
     * @param boolean|array|\Common\Core\Serializer\ResultSet $data
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function _handleViewWithData($data, $statusCode = null)
    {
        if (empty($this->_requestFormat)) {
            throw new ResponseFormatException();
        }

        switch (strtolower($this->_requestFormat)) {
            case 'html':
                return $this->_handlerHtmlFormat($data, $statusCode);
                break;
            default:
                return $this->_handlerOtherFormat($data, $statusCode);
        }
    }

    /**
     * Вспомогательный метод возврата результата клиенту
     *
     * @param \Common\Core\Facade\Search\QueryFactory\SearchServiceInterface $searchService
     * @param string $keyFieldName Название поля в ключе объекта вывода
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function returnDataResult(SearchServiceInterface $searchService, $keyFields)
    {
        $keyFieldNames = is_array($keyFields) ? $keyFields : [$keyFields];

        /** общая информация по запросу */
        $data['info'] = $searchService->getTotalHits();

        /**
         * постарничный адаптер срабатывает только для единичного поиска по типу
         * в случае если мы проводим поиск по нескольким типам, тогда пагинация все равно будет 1 для всех
         * т.е. общая
         */
        $data['pagination'] = $searchService->getPaginationAdapter($this->getSkip(), $this->getCount());

        foreach ($keyFieldNames as $keyField) {
            $total = $searchService->getTotalResults();
            if (isset($total[$keyField])) {
                $data['info']['searchType'][$keyField] = $searchService->getTotalHits();

                $data['items'][$keyField] = $total[$keyField];
                if (count($keyFieldNames) > 1) {
                    $data['info'][$keyField] = [
                        'totalHits' => count($data[$keyField]),
                    ];
                }
            }
        }

        return $this->_handleViewWithData($data);

    }

    /**
     * Вовращает ответ в HTML формате
     *
     * @param boolean|array|\Common\Core\Serializer\ResultSet $data
     * @param integer $statusCode
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function _handlerHtmlFormat($data, $statusCode = null)
    {
        $response = $this->render($this->_template, [self::RESULT => $data]);
        $response->setStatusCode($statusCode);

        return $response;
    }

    /**
     * Вовращает ответ в другом формате НЕ html
     * формат может быть json|xml|rss
     *
     * @param boolean|array|\Common\Core\Serializer\ResultSet $data
     * @param integer $statusCode
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function _handlerOtherFormat($data, $statusCode = null)
    {
        $view = $this->view();
        $xmlWrapper = new XMLWrapper();
        $isError = is_array($data) && array_key_exists(self::ERROR, $data);

        if (!$isError) {
            $xmlWrapper->data = [self::RESULT => $data];
        } else {

            $xmlWrapper->data = $data;
            $view->setStatusCode($statusCode === null ? Response::HTTP_INTERNAL_SERVER_ERROR : $statusCode);
        }

        $view->setStatusCode($statusCode);

        return $this->handleView($view->setData($xmlWrapper));
    }

    /**
     * Ответ с ошибкой.
     *
     * @param mixed $errorMessage Текст сообщения
     * @param int $errorCode Код ошибки
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function _handleViewWithError($errorMessage, $errorCode = Response::HTTP_INTERNAL_SERVER_ERROR)
    {
        if ($errorMessage instanceof \Exception) {
            $generatedErrorCode = ($errorMessage->getCode() == 0 ? $errorCode : $errorMessage->getCode());

            return $this->_handleViewWithData(
                $this->buildError($errorMessage->getMessage(), $generatedErrorCode),
                $generatedErrorCode
            );
        }

        return $this->_handleViewWithData(
            $this->buildError($errorMessage, $errorCode),
            $errorCode
        );
    }

    /**
     * Заполняет и проверяет модель из реквеста.
     * Возвращает либо null, в лучае отсутсвия ошибок, либо строку ошибки для отправки на клиент.
     *
     * @param object $formModel
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return null|string
     */
    public function loadAndValidateModel($formModel, Request $request = null)
    {
        if (is_null($request)) {
            $request = $this->getRequest();
        }

        $loader = $this->getModelLoaderService();

        return $loader->loadAndValidateModel($formModel, $request);
    }

    /**
     * Генерируем ответ ошибки
     *
     * @param mixed $message Массив текстов ответов ошибки или текст ошибки
     * @param int $code Код ошибки
     * @return array Массив с ошибкой
     */
    private function buildError($message, $code = -1)
    {
        return [
            self::ERROR => [
                self::TEXT          => (is_array($message) ? $message : [$message]),
                self::TEXT_TRANSLIT => (is_array($message) ?
                    array_map(function ($msg) {
                        return $this->_translit($msg);
                    }, $message) :
                    [$this->_translit($message)]
                ),
                self::ERROR_CODE    => $code,
            ],
        ];
    }

    /**
     * Транслитерация текст
     *
     * @param string $str Текст который нужно конвертировать
     */
    private function _translit($str)
    {

        $translit = [
            '0' => "0",
            '1' => "1",
            '2' => "2",
            '3' => "3",
            '4' => "4",
            '5' => "5",
            '6' => "6",
            '7' => "7",
            '8' => "8",
            '9' => "9",
            'а' => "a",
            'б' => "b",
            'в' => "v",
            'г' => "g",
            'д' => "d",
            'е' => "e",
            'ё' => "yo",
            'ж' => "zh",
            'з' => "z",
            'и' => "i",
            'й' => "y",
            'к' => "k",
            'л' => "l",
            'м' => "m",
            'н' => "n",
            'о' => "o",
            'п' => "p",
            'р' => "r",
            'с' => "s",
            'т' => "t",
            'у' => "u",
            'ф' => "f",
            'х' => "h",
            'ц' => "c",
            'ч' => "ch",
            'ш' => "sh",
            'щ' => "xh",
            'ь' => "",
            'ы' => "y",
            'ъ' => "",
            'э' => "e",
            'ю' => "yu",
            'я' => "ya",
            'А' => "A",
            'Б' => "B",
            'В' => "V",
            'Г' => "G",
            'Д' => "D",
            'Е' => "E",
            'Ё' => "YO",
            'Ж' => "ZH",
            'З' => "Z",
            'И' => "I",
            'Й' => "Y",
            'К' => "K",
            'Л' => "L",
            'М' => "M",
            'Н' => "N",
            'О' => "O",
            'П' => "P",
            'Р' => "R",
            'С' => "S",
            'Т' => "T",
            'У' => "U",
            'Ф' => "F",
            'Ч' => "H",
            'Ц' => "C",
            'Ч' => "CH",
            'Ш' => "SH",
            'Щ' => "XH",
            'Ь' => "",
            'Ы' => "Y",
            'Ъ' => "",
            'Э' => "E",
            'Ю' => "YU",
            'Я' => "YA",
        ];

        $res = strtr($str, $translit);

        return $res;
    }

    /**
     * @return \Common\Core\Loader\JSONModelLoader
     */
    protected function getModelLoaderService()
    {
        return $this->get('rp_common.model_loader');
    }

    /**
     * Получаем сервис поиска
     *
     * @return \Common\Core\Facade\Search\QueryFactory\SearchEngineInterface
     */
    protected function getSearchService()
    {
        return $this->get('rp_search.search.engine');
    }

    /**
     * Получаем сервис поиска пользователей (людей)
     * через еластик
     *
     * @return \RP\SearchBundle\Services\PeopleSearchService
     */
    public function getPeopleSearchService()
    {
        return $this->get('rp_search.search_service.people');
    }

    /**
     * Получаем сервис поиска мест
     * через еластик
     *
     * @return \RP\SearchBundle\Services\PlacesSearchService
     */
    public function getPlacesSearchService()
    {
        return $this->get('rp_search.search_service.places');
    }

    /**
     * Получаем сервис поиска маркеров
     * через еластик
     *
     * @return \RP\SearchBundle\Services\CommonSearchService
     */
    public function getCommonSearchService()
    {
        return $this->get('rp_search.search_service.common');
    }

    /**
     * Получаем сервис поиска событий
     * через еластик
     *
     * @return \RP\SearchBundle\Services\EventsSearchService
     */
    public function getEventsSearchService()
    {
        return $this->get('rp_search.search_service.events');
    }
}
