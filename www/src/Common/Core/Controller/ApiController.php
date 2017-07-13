<?php
/**
 * Main API controller
 * this controller contains all pre-defined methods
 * which help to you in the futures
 */

namespace Common\Core\Controller;

use Common\Core\Constants\RequestConstant;
use Common\Core\Exceptions\ResponseFormatException;
use Common\Core\Facade\Search\QueryFactory\SearchServiceInterface;
use Common\Core\Serializer\XMLWrapper;
use FOS\RestBundle\Controller\FOSRestController;
use RP\SearchBundle\Services\AbstractSearchService;
use RP\SearchBundle\Services\NewsFeedSearchService;
use RP\SearchBundle\Services\Transformers\AbstractTransformer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Основной абстрактный класс контроллера
 */
abstract class ApiController extends FOSRestController
{
    use ControllerTrait;

    /**
     * Включаем ли ответ в контекст массива
     *
     * @const bool INCLUDE_IN_CONTEXT
     */
    const INCLUDE_IN_CONTEXT = true;

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
        $user = $this->getUser();

        /** @var ID пользователя из объекта security (авторизация по токену) */
        $this->requestUserId = empty($user) ? null : $user->getUsername();
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

        $filtered = array_filter(explode(self::MARKER_FILTER_DELIMITER, $types), function ($type) {
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
        // эта часть кода неправильно передается в контроллер, поэтому закомментирована
        // если потребуется восстановить exception, нужно раскоментировать блок и заменить return $this->_handleViewWithError на throw
        // также этот exception не даст делать анонимные запросы (например запрос поиска город можно сделать без авторизации)

        // if (is_null($this->requestUserId)) {
        //     throw new BadRequestHttpException(
        //         'Не указан userId пользователя',
        //         null,
        //         Response::HTTP_BAD_REQUEST
        //     );
        // }

        return $this->requestUserId;
    }

    /**
     * Полуачем ID города при запросе к API
     *
     * @return string $requestCityId
     */
    public function getRequestCityId()
    {
        // эта часть кода неправильно передается в контроллер, поэтому закомментирована
        // если потребуется восстановить exception, нужно раскоментировать блок и заменить return $this->_handleViewWithError на throw

        // if (is_null($this->requestCityId)) {
        //     return $this->_handleViewWithError(
        //         new BadRequestHttpException(
        //             'Не задан город для поиска по городу',
        //             null,
        //             Response::HTTP_BAD_REQUEST
        //         )
        //     );
        // }

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
     * @param int|NULL $statusCode Код ответа
     * @param bool $invokeTo Включаем в объект ответа полученные данные (default: true)
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function _handleViewWithData($data, $statusCode = null, $invokeTo = self::INCLUDE_IN_CONTEXT)
    {
        if (empty($this->_requestFormat)) {
            throw new ResponseFormatException();
        }

        switch (strtolower($this->_requestFormat)) {
            case 'html':
                return $this->_handlerHtmlFormat($data, $statusCode);
                break;
            default:
                return $this->_handlerOtherFormat($data, $statusCode, $invokeTo);
        }
    }

    /**
     * Вспомогательный метод возврата результата клиенту
     *
     * @param \Common\Core\Facade\Search\QueryFactory\SearchServiceInterface $searchService
     * @param string $keyFields Название поля в ключе объекта вывода
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
    private function _handlerHtmlFormat($data, $statusCode = null)
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
     * @param bool $invokeTo Включи ли ответ в массив вложенный
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function _handlerOtherFormat($data, $statusCode = null, $invokeTo = self::INCLUDE_IN_CONTEXT)
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
        $view->setData(($invokeTo === self::INCLUDE_IN_CONTEXT ? $xmlWrapper : $xmlWrapper->data[self::RESULT]));

        return $this->handleView($view);
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
     * Возвращаем данные для старых версий приложения
     * для того чтобы предусмотреть возможность
     * корректного отображения данных в старых приложениях
     * Предополагается что сервис уже извлек данные и их надо
     * всего-лишь промапить для корректной отдачи конечному пользователю
     *
     * @param AbstractSearchService $searchService
     * @return array
     */
    public function getVersioningData(AbstractSearchService $searchService)
    {
        // набор данных полученных из сервиа поиска
        $resultData = $searchService->getTotalResults();

        // набор общей информации
        $resultInfo = $searchService->getTotalHits();

        if (!empty($resultData)) {
            $this->restructTagsField($resultData);
            $this->restructLocationField($resultData);
            $resultData = $this->changeKeysName($resultData);
            $resultData = $this->excludeEmptyValue($resultData);
        }

        return [
            'results' => $resultData,
            'info'    => (isset($resultInfo['searchType']) ? $resultInfo['searchType'] : $resultInfo),
        ];
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
     * @return string
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
     * ДЛя новыйх версий ответа
     * необходимо всегда вкладывать в респонс
     * объекты info,pagination,items
     *
     * @param AbstractSearchService $searchService
     * @param string|null $context КОнтекст набора данных (ключ объекта где лежат данные на входе)
     * @param array $params Допнительные данные для выдачи результата (склеиваем )
     * @return array
     */
    public function getNewFormatResponse(AbstractSearchService $searchService, $context = null, $params = [])
    {
        $result = [];
        $items = $searchService->getTotalResults();
        $totalHits = $searchService->getTotalHits();


        if (!empty($items)) {
            $searchService->revertToScalarTagsMatchFields($items);
            $result = [
                'info'       => !is_null($context) && isset($totalHits[$context]) ? $totalHits[$context] : $totalHits,
                'pagination' => $searchService->getPaginationAdapter(
                    $this->getSkip(),
                    $this->getCount()
                ),
                'items'      => !is_null($context) ? $items[$context] : $items
            ];
        }

        return array_merge(
            $result,
            $params
        );
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

    /**
     * Получаем сервис поиска стран
     * через еластик
     *
     * @return \RP\SearchBundle\Services\CountrySearchService
     */
    public function getCountrySearchService()
    {
        return $this->get('rp_search.search_service.country');
    }

    /**
     * Получаем сервис поиска городов
     * через еластик
     *
     * @return \RP\SearchBundle\Services\CitySearchService
     */
    public function getCitySearchService()
    {
        return $this->get('rp_search.search_service.city');
    }

    /**
     * Получаем сервис поиска городов
     * через еластик
     *
     * @return \RP\SearchBundle\Services\ChatMessageSearchService
     */
    public function getChatMessageSearchService()
    {
        return $this->get('rp_search.search_service.chat_message');
    }

    /**
     * Получаем сервис поиска постов
     * через еластик
     *
     * @return NewsFeedSearchService
     */
    public function getNewsFeedSearchService()
    {
        return $this->get('rp_search.search_service.news_feed.posts');
    }

    /**
     * ДОбавляем динамические поля к событию
     * что нельзя сделать для хранения в еластике
     *
     * @param array $events
     * @param string $userId
     * @return EventsSearchService
     */
    public function extractWillComeFriends(&$events, $userId)
    {
        //willComeUsers
        //willComeFriends

        if (is_null($events)) {
            return null;
        }

        $events = AbstractTransformer::is_assoc($events) ? [$events] : $events;
        foreach ($events as $key => &$event) {
            //$event['willComeFriends'] = [];
            if (isset($event['willComeUsers']) && !empty($event['willComeUsers'])) {
                $event['willComeUsers'] = array_combine(array_column($event['willComeUsers'], 'id'),
                    $event['willComeUsers']);
                $willComeFriends = [];
                $willComeUsers = [];
                foreach ($event['willComeUsers'] as $idUser => $users) {
                    if (isset($users['friendList']) && !empty($users['friendList']) && in_array($userId,
                            $users['friendList'])
                    ) {
                        $willComeFriends[] = $users;
                    } else {
                        $willComeUsers[] = $users;
                    }
                }

                $event['willComeUsers'] = array_values($willComeUsers);
                $event['willComeFriends'] = $willComeFriends;
            }
        }
    }

    /**
     * Преобразует полученнйы параметр в boolan значение
     * потому что на входе параметр может быть либо строка "true"|"false" либо число 0|1
     *
     * @param mixed $param
     * @param bool $defaultValue Значение по умолчанию
     * @return bool (default: false)
     */
    protected function getBoolRequestParam($param, $defaultValue = false)
    {
        if (!is_null($param) && !empty($param)) {
            if (is_string($param)) {
                $param = mb_strtolower($param);
                switch ($param) {
                    case 'true':
                    case '1':
                        return true;
                        break;
                    case 'false':
                    case '0':
                        return false;
                        break;
                    default:
                        return $defaultValue;
                }
            }

            if (is_int($param)) {
                if ($param >= 1) {
                    return true;
                } else {
                    return false;
                }
            }
        }

        return $defaultValue;
    }
}
