<?php
/**
 * Класс поиска по сообщениям и людям из чата
 */
namespace RP\SearchBundle\Controller;

use Common\Core\Controller\ApiController;
use Common\Core\Exceptions\SearchServiceException;
use Common\Core\Facade\Service\User\UserProfileService;
use Elastica\Exception\ElasticsearchException;
use RP\SearchBundle\Services\Mapping\ChatMessageMapping;
use Symfony\Component\HttpFoundation\Request;
use Common\Core\Constants\RequestConstant;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SearchChatMessageController extends ApiController
{


    /**
     * Метод осуществляющий поиск по сообщениям
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @return \Symfony\Component\HttpFoundation\Response Возвращаем ответ
     */
    public function searchChatMessageAction(Request $request)
    {
        try {
            $version = $request->get(RequestConstant::VERSION_PARAM, RequestConstant::NULLED_PARAMS);

            /** @var Текст запроса */
            /** @var Текст запроса */
            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM);
            $searchText = !empty($searchText) ? $searchText : RequestConstant::NULLED_PARAMS;

            /*if (is_null($searchText)) {
                return $this->_handleViewWithError(
                    new BadRequestHttpException(
                        'Не указана поисковая строка searchText',
                        null,
                        Response::HTTP_BAD_REQUEST
                    )
                );
            }*/

            /** @var ID чата в котором можем искать */
            $chatId = $request->get(RequestConstant::CHAT_ID_PARAM);

            // получаем из запроса ID пользователя
            $userId = $this->getRequestUserId();

            $chatSearchService = $this->getChatMessageSearchService();

            // ужасный костыль после перехода к новому сервису надо убрать
            if (!is_null($version) && (int)$version == RequestConstant::DEFAULT_VERSION) {
                $chatSearchService->setOldFormat(true);
            }

            $chatMessages = $chatSearchService->searchByChatMessage(
                $userId,
                $searchText,
                $chatId,
                $this->getSkip(),
                $this->getCount()
            );

            if (!is_null($version) && (int)$version === RequestConstant::DEFAULT_VERSION) {
                $oldFormat = $this->getVersioningData($chatSearchService);
                $data = [];

                if( isset($oldFormat['results']) && !empty($oldFormat['results']) )
                {
                    $data = $chatSearchService->peopleTransformer->transform(
                        $oldFormat['results'],
                        ChatMessageMapping::CONTEXT
                    );
                }


                return $this->_handleViewWithData(
                    $data,
                    null,
                    !self::INCLUDE_IN_CONTEXT
                );
            }

            return $this->_handleViewWithData(array_merge(
                [
                    'info' => $chatSearchService->getTotalHits(),
                ],
                $chatMessages ?: []
            ));

        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }
}