<?php
/**
 * Класс общего поиска по всем коллекциям
 */
namespace RP\SearchBundle\Controller;

use Common\Core\Controller\ApiController;
use Common\Core\Facade\Service\User\UserProfileService;
use Elastica\Exception\ElasticsearchException;
use RP\SearchBundle\Services\Mapping\PeopleSearchMapping;
use Symfony\Component\HttpFoundation\Request;
use Common\Core\Constants\RequestConstant;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SearchCommonController extends ApiController
{
    /**
     * Метод осуществляющий глобальный поиск
     * в контексте всех имеющихся типов в базе еластика
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @param string|null $filterType Категория запроса (people,places,discounts...)
     * @return \Symfony\Component\HttpFoundation\Response Возвращаем ответ
     */
    public function searchCommonByFilterAction(Request $request, $filterType)
    {
        try {
            $version = $request->get(RequestConstant::VERSION_PARAM, RequestConstant::NULLED_PARAMS);
            $filterType = ($filterType == '_all' ? RequestConstant::NULLED_PARAMS : $filterType);

            /** @var Текст запроса */
            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM);
            $searchText = (mb_strlen($searchText) <= 2 ? RequestConstant::NULLED_PARAMS : trim($searchText));

            // получаем из запроса ID пользователя
            $userId = $this->getRequestUserId();

            // получаем ID города если он указан в запросе
            $cityId = $request->get(RequestConstant::CITY_SEARCH_PARAM, RequestConstant::NULLED_PARAMS);
            $cityId = !empty($cityId) ? $cityId : RequestConstant::NULLED_PARAMS;

            if (is_null($cityId) && is_null($searchText)) {
                return $this->_handleViewWithError(new BadRequestHttpException(
                    'Необходимо указать один из обязательных параметров запроса (cityId или searchText)'
                ), Response::HTTP_BAD_REQUEST);
            }

            $commonSearchService = $this->getCommonSearchService();
            // ужасный костыль после перехода к новому сервису надо убрать
            if (!is_null($version) && (int)$version === RequestConstant::DEFAULT_VERSION) {
                $commonSearchService->setOldFormat(true);
            }

            $searchData = $commonSearchService->commonSearchByFilters(
                $userId,
                $searchText,
                $cityId,
                $this->getGeoPoint(),
                $filterType,
                $this->getSkip(),
                $this->getCount()
            );

            if (!is_null($version) && (int)$version === RequestConstant::DEFAULT_VERSION) {

                $oldFormat = $this->getVersioningData($commonSearchService);
                $data = [];
                if(!empty($oldFormat))
                {
                    $keys = array_keys($oldFormat['results']);

                    foreach ($keys as $format)
                    {

                        $data[$format] = $commonSearchService->peopleTransformer->transform(
                            $oldFormat['results'],
                            $format,
                            'item'
                        );
                    }

                    $data = $this->excludeEmptyValue($data);
                    $data = [
                        'results' => $data,
                        'info' => $oldFormat['info']
                    ];
                }



                return $this->_handleViewWithData(
                    $data,
                    null,
                    !self::INCLUDE_IN_CONTEXT
                );
            }

            return $this->_handleViewWithData(array_merge(
                [
                    'info' => $commonSearchService->getTotalHits(),
                ],
                $searchData ?: []
            ));

        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }
}