<?php
/**
 * Основной контроллер поиска по людям
 */
namespace RP\SearchBundle\Controller;

use Common\Core\Controller\ApiController;
use Elastica\Exception\ElasticsearchException;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;
use Symfony\Component\HttpFoundation\Request;
use Common\Core\Constants\RequestConstant;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SearchUsersController extends ApiController
{

    /**
     * Ключ контекста объекта ответа клиенту
     *
     * @const string KEY_FIELD_RESPONSE
     */
    const KEY_FIELD_RESPONSE = 'people';

    /**
     * Поиск пользователей по имени/фамилии
     * искомая строка содержиться либо в начале либо в середине
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchUsersByNameAction(Request $request)
    {
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

        /** получаем сервис поиска */
        $peopleSearchService = $this->getPeopleSearchService();
        /** создаем запрос для поиска по имени/фамилии пользователей */
        $people = $peopleSearchService->searchPeopleByName($userId, $searchText, $this->getGeoPoint(), $this->getSkip(), $this->getCount());

        /** выводим результат */
        return $this->returnDataResult($peopleSearchService, self::KEY_FIELD_RESPONSE);
    }

    /**
     * Поиск пользователей по городу
     * выводим всех найденных пользователей в указанном городе
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchUsersByCityAction(Request $request)
    {
        /** @var Текст запроса (в случае если ищем по имени) */
        $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM, RequestConstant::NULLED_PARAMS);
        /** @var ID города */
        $cityId = $this->getRequestCityId();
        /** @var ID пользователя */
        $userId = $this->getRequestUserId();

        $peopleSearchService = $this->getPeopleSearchService();
        $people = $peopleSearchService->searchPeopleByCityId($userId, $cityId, $this->getGeoPoint(), $searchText, $this->getSkip(), $this->getCount());

        /** выводим результат */
        return $this->returnDataResult($peopleSearchService, self::KEY_FIELD_RESPONSE);
    }

    /**
     * Поиск пользователей в друзъях
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchUsersByFriendAction(Request $request)
    {
        /** @var Текст запроса */
        $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM, RequestConstant::NULLED_PARAMS);

        /** @var ID пользователя */
        $userId = $this->getRequestUserId();

        $peopleSearchService = $this->getPeopleSearchService();
        $people = $peopleSearchService->searchPeopleFriends($userId, $this->getGeoPoint(), $searchText, $this->getSkip(), $this->getCount());

        /** выводим результат */
        return $this->returnDataResult($peopleSearchService, self::KEY_FIELD_RESPONSE);
    }

    /**
     * Поиск пользователей которые могут помочь
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchUsersHelpOffersAction(Request $request)
    {
        /** @var Текст запроса */
        $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM, RequestConstant::NULLED_PARAMS);

        /** @var ID пользователя */
        $userId = $this->getRequestUserId();

        $peopleSearchService = $this->getPeopleSearchService();
        $peopleHelpOffers = $peopleSearchService->searchPeopleHelpOffers(
            $userId,
            $this->getGeoPoint(),
            $searchText,
            $this->getSkip(),
            $this->getCount()
        );

        /** выводим результат */
        return $this->returnDataResult($peopleSearchService, self::KEY_FIELD_RESPONSE);
    }

    /**
     * Поиск пользователя по его ID
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @param string $userId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchUsersByIdAction(Request $request, $userId)
    {
        $peopleSearchService = $this->getPeopleSearchService();

        try {
            $user = $peopleSearchService->getUserById($userId);

            if (!is_null($user)) {
                return $this->_handleViewWithData($user->toArray());
            }

            return null;

        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

}
