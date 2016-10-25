<?php
/**
 * Контроллер поиска маркеров
 * по нескольким типам (например: people,places)
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

class SearchMarkersController extends ApiController
{
    /**
     * Разделитель фильтра маркеров
     *
     * @var string MARKER_FILTER_DELIMITER
     */
    const MARKER_FILTER_DELIMITER = ';';

    public function searchMarkersByTypeAction(Request $request, $filterTypes)
    {
        /** разделяем фильтры по разделителю (разделитель может быть любым из спец.символов) */
        $types = preg_replace('/[^\w]+/s', self::MARKER_FILTER_DELIMITER, $filterTypes);
        $types = explode(self::MARKER_FILTER_DELIMITER, $types);

        /** @var Текст запроса */
        $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM, RequestConstant::NULLED_PARAMS);

        $markersSearchService = $this->getMarkersSearchService();
        $markers = $markersSearchService->searchMarkersByTypes($types, $searchText, $this->getSkip(), $this->getCount());

        return $this->_handleViewWithData($markers);
    }
}