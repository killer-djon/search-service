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
use RP\SearchBundle\Services\Transformers\AbstractTransformer;
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
            $version = $request->get(RequestConstant::VERSION_PARAM, RequestConstant::NEW_DEFAULT_VERSION);

            /** @var Текст запроса */
            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM);
            $searchText = (!is_null($searchText) && !empty($searchText) ? $searchText : RequestConstant::NULLED_PARAMS);

            /** @var ID чата в котором можем искать */
            $chatId = $request->get(RequestConstant::CHAT_ID_PARAM);

            // получаем из запроса ID пользователя
            $userId = $this->getRequestUserId();

            $chatSearchService = $this->getChatMessageSearchService();

            //$groupChat = ( is_null($searchText) ? true : false );
            $groupChat = ( is_null($chatId) || empty($chatId) ? true : false );

            $chatMessages = $chatSearchService->searchByChatMessage(
                $userId,
                $searchText,
                $chatId,
                $groupChat,
                $this->getSkip(),
                $this->getCount()
            );

            if( $groupChat )
            {
                $chatMessages = $chatSearchService->chatMessageTransformer->transformAggregations(
                    $chatSearchService->getAggregations(),
                    ChatMessageMapping::CONTEXT,
                    ChatMessageMapping::CONTEXT,
                    ChatMessageMapping::LAST_CHAT_MESSAGE
                );

            }

            if (!is_null($version) && (int)$version === RequestConstant::DEFAULT_VERSION) {

                $chatMessages = $chatSearchService->chatMessageTransformer->transform(
                    $chatMessages,
                    ChatMessageMapping::CONTEXT
                );

                return $this->_handleViewWithData(
                    $chatMessages[ChatMessageMapping::CONTEXT],
                    null,
                    !self::INCLUDE_IN_CONTEXT
                );
            }

            return $this->_handleViewWithData(array_merge(
                [
                    'info' => array_merge(
                        $chatSearchService->getTotalHits(),
                        [
                            'totalChats' => count($chatSearchService->getAggregations())
                        ]
                    ),
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