<?php
/**
 * Контроллер для поиска событий по фильтру
 */
namespace RP\SearchBundle\Controller;

use Common\Core\Controller\ApiController;
use Common\Core\Controller\ControllerTrait;
use Common\Core\Exceptions\SearchServiceException;
use Symfony\Component\HttpFoundation\Request;
use Common\Core\Constants\RequestConstant;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SearchEventsController extends ApiController
{

    /**
     * Ключ контекста объекта ответа клиенту
     *
     * @const string KEY_FIELD_RESPONSE
     */
    const KEY_FIELD_RESPONSE = 'events';

    /**
     * Поиск событий по имени
     * если имя/слово не заданно то выводим все имеющиеся события
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @return \Symfony\Component\HttpFoundation\Response Ответ
     */
    public function searchEventsByNameAction(Request $request)
    {
        try {
            /** @var Текст запроса */
            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM, RequestConstant::NULLED_PARAMS);
            if (is_null($searchText)) {
                return $this->_handleViewWithError(
                    new BadRequestHttpException(
                        'Не указана поисковая строка searchText',
                        null,
                        Response::HTTP_BAD_REQUEST
                    )
                );
            }

            if (mb_strlen($searchText) <= 2) {
                return $this->_handleViewWithError(
                    new BadRequestHttpException(
                        'Поисковая строка должны быть больше двух символов',
                        null,
                        Response::HTTP_BAD_REQUEST
                    )
                );
            }

            /** @var ID пользователя */
            $userId = $this->getRequestUserId();
            $eventsSearchService = $this->getEventsSearchService();

            $events = $eventsSearchService->searchEventsByName(
                $userId,
                $searchText,
                $this->getGeoPoint(),
                $this->getSkip(),
                $this->getCount()
            );

            return $this->returnDataResult($eventsSearchService, self::KEY_FIELD_RESPONSE);
        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Поиск событий в городе
     * если слово/имя не заданно тогда выводим все в определенном городе
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @param string $cityId ID города в котором проводим поиск
     * @return \Symfony\Component\HttpFoundation\Response Ответ
     */
    public function searchEventsByCityAction(Request $request, $cityId)
    {
        try {
            /** @var ID пользователя */
            $userId = $this->getRequestUserId();

            /** @var ID города */
            $cityId = $this->getRequestCityId();

            /** @var Текст запроса */
            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM, RequestConstant::NULLED_PARAMS);

            $eventsSearchService = $this->getEventsSearchService();

            $events = $eventsSearchService->searchEventsByCity(
                $userId,
                $cityId,
                $this->getGeoPoint(),
                $searchText,
                $this->getSkip(),
                $this->getCount()
            );

            return $this->returnDataResult($eventsSearchService, self::KEY_FIELD_RESPONSE);
        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Находим единственное событие по его ID
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @param string $eventId ID события которое ищем
     * @return \Symfony\Component\HttpFoundation\Response Ответ
     */
    public function searchEventsByIdAction(Request $request, $eventId)
    {
        try {
            /** @var ID пользователя */
            $userId = $this->getRequestUserId();

            $eventsSearchService = $this->getEventsSearchService();
            $event = $eventsSearchService->getEventById($userId, $eventId);

            return $this->_handleViewWithData($event);
        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Ищем все события по заданному месту
     * т.е. все события в одном месте
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @param string $placesId ID мест/места в котором ищем все события
     * @return \Symfony\Component\HttpFoundation\Response Ответ
     */
    public function searchEventsByPlacesIdAction(Request $request, $placesId)
    {
        try{
            /** @var ID пользователя */
            $userId = $this->getRequestUserId();

            $places = $this->getParseFilters($placesId);
            /** @var Текст запроса */
            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM, RequestConstant::NULLED_PARAMS);

            $eventsSearchService = $this->getEventsSearchService();
            $eventsSearchService->searchEventsByPlacesId($userId, $places, $this->getGeoPoint(), $searchText, $this->getSkip(), $this->getCount());

            return $this->returnDataResult($eventsSearchService, self::KEY_FIELD_RESPONSE);

        }catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }
}