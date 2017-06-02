<?php
/**
 * Класс поиска по сообщениям и людям из чата
 */

namespace RP\SearchBundle\Controller;

use Common\Core\Constants\RequestConstant;
use Common\Core\Controller\ApiController;
use Common\Core\Exceptions\SearchServiceException;
use RP\SearchBundle\Services\Mapping\ChatMessageMapping;
use RP\SearchBundle\Services\Transformers\AbstractTransformer;
use Symfony\Component\HttpFoundation\Request;

class SearchChatMessageController extends ApiController
{

    /**
     * Метод осуществляющий вывод всех собщений чата
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @param string $chatId ID чата для которого выводим все сообщения
     * @return \Symfony\Component\HttpFoundation\Response Возвращаем ответ
     */
    public function getChatMessageAction(Request $request, $chatId)
    {
        try {
            $version = $request->get(RequestConstant::VERSION_PARAM, RequestConstant::DEFAULT_VERSION);

            /** @var string $searchText Текст запроса */
            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM);
            $searchText = (!is_null($searchText) && !empty($searchText) ? $searchText : RequestConstant::NULLED_PARAMS);

            // получаем из запроса ID пользователя
            $userId = $this->getRequestUserId();

            $chatSearchService = $this->getChatMessageSearchService();

            $messages = $chatSearchService->searchByChatMessage(
                $userId,
                $searchText,
                $chatId,
                false,
                $this->getSkip(),
                $this->getCount()
            );

            if (empty($messages)) {
                return $this->_handleViewWithData([]);
            }

            $chatMessages = [];

            foreach ($messages[ChatMessageMapping::CONTEXT] as &$chatMessage) {

                $key = array_search($userId, array_column($chatMessage['recipients'], 'id'));
                $isDeleted = (isset($chatMessage['recipients'][$key]['isDeleted']) ? $chatMessage['recipients'][$key]['isDeleted'] : false);

                if ($chatMessage['recipients'][$key]['id'] == $userId && $isDeleted == false) {
                    $chatMessages[ChatMessageMapping::CONTEXT][] = $chatMessage;
                }
            }

            if (empty($chatMessages)) {
                return $this->_handleViewWithData([]);
            }

            if (!is_null($version) && (int)$version === RequestConstant::DEFAULT_VERSION) {

                $chatMessages[ChatMessageMapping::CONTEXT] = $chatSearchService->chatMessageTransformer->transformForSearch(
                    $chatMessages,
                    ChatMessageMapping::CONTEXT,
                    $userId
                );

                $totalHits = $chatSearchService->getTotalHits();
                $totalHits['totalHits'] = count($chatMessages[ChatMessageMapping::CONTEXT]);

                return $this->_handleViewWithData(
                    [
                        'messages' => $chatMessages[ChatMessageMapping::CONTEXT],
                        'info'     => $totalHits,
                    ],
                    null,
                    !self::INCLUDE_IN_CONTEXT
                );
            }

            $totalHits = $chatSearchService->getTotalHits();
            $totalHits['totalHits'] = count($chatMessages[ChatMessageMapping::CONTEXT]);

            return $this->_handleViewWithData(array_merge(
                [
                    'info'       => $totalHits,
                    'pagination' => $chatSearchService->getPaginationAdapter($this->getSkip(), $this->getCount()),
                ],
                $chatMessages ?: []
            ));
        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Метод осуществляющий поиск по сообщениям
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @return \Symfony\Component\HttpFoundation\Response Возвращаем ответ
     */
    public function searchChatMessageAction(Request $request)
    {
        try {
            $version = $request->get(RequestConstant::VERSION_PARAM, RequestConstant::DEFAULT_VERSION);

            /** @var string $searchText Текст запроса */
            $searchText = $request->get(RequestConstant::SEARCH_TEXT_PARAM);
            $searchText = (!is_null($searchText) && !empty($searchText) ? $searchText : RequestConstant::NULLED_PARAMS);

            // получаем из запроса ID пользователя
            $userId = $this->getRequestUserId();

            $chatSearchService = $this->getChatMessageSearchService();

            if (!is_null($version) && (int)$version === RequestConstant::DEFAULT_VERSION) {
                $chatSearchService->setOldFormat(true);
            }

            $chatMessages = $chatSearchService->searchByChatMessage(
                $userId,
                $searchText,
                null,
                false,
                $this->getSkip(),
                $this->getCount()
            );

            if (empty($chatMessages)) {
                return $this->_handleViewWithData([]);
            }

            if (!is_null($version) && (int)$version === RequestConstant::DEFAULT_VERSION) {

                if (!is_null($searchText) && !empty($searchText)) {
                    $chatMessages[ChatMessageMapping::CONTEXT] = $chatSearchService->chatMessageTransformer->transformForSearch(
                        $chatMessages,
                        ChatMessageMapping::CONTEXT,
                        $userId,
                        'item'
                    );
                } else {
                    $chatMessages[ChatMessageMapping::CONTEXT] = $chatSearchService->chatMessageTransformer->transform(
                        $chatMessages,
                        ChatMessageMapping::CONTEXT,
                        'item'
                    );
                }

                return $this->_handleViewWithData(
                    $chatMessages[ChatMessageMapping::CONTEXT],
                    null,
                    !self::INCLUDE_IN_CONTEXT
                );
            }

            return $this->_handleViewWithData(array_merge(
                [
                    'info'       => $chatSearchService->getTotalHits(),
                    'pagination' => $chatSearchService->getPaginationAdapter($this->getSkip(), $this->getCount()),
                ],
                $chatMessages ?: []
            ));

        } catch (SearchServiceException $e) {
            return $this->_handleViewWithError($e);
        } catch (\HttpResponseException $e) {
            return $this->_handleViewWithError($e);
        }
    }

    /**
     * Метод для поулчения единственного чата по ID
     * так же мы включаем в вывод последниие count сообщений
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @param string $chatId ID чата который мы ищем
     * @return \Symfony\Component\HttpFoundation\Response Возвращаем ответ
     */
    public function searchSingleChatAction(Request $request, $chatId)
    {
        $version = $request->get(RequestConstant::VERSION_PARAM, RequestConstant::DEFAULT_VERSION);

        // получаем из запроса ID пользователя
        $userId = $this->getRequestUserId();

        $chatSearchService = $this->getChatMessageSearchService();

        $chatWithMessages = $chatSearchService->searchSingleChat(
            $userId,
            $chatId,
            $this->getSkip(),
            $this->getCount()
        );

        if (empty($chatSearchService->getAggregations())) {
            return $this->_handleViewWithData([]);
        }

        $chatWithMessages[ChatMessageMapping::CONTEXT] = AbstractTransformer::path($chatSearchService->getAggregations(), '0');
        $chatWithMessages[ChatMessageMapping::CONTEXT] = $chatSearchService->chatMessageTransformer->trasformSingleResult(
            $chatWithMessages,
            ChatMessageMapping::CONTEXT
        );

        if (empty($chatWithMessages)) {
            return $this->_handleViewWithData([]);
        }

        return $this->_handleViewWithData([
            ChatMessageMapping::CONTEXT => $chatWithMessages[ChatMessageMapping::CONTEXT],
            'info'                      => $chatSearchService->getTotalHits(),
        ]);
    }

    /**
     * Метод осуществляющий вывод списка чатов
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Объект запроса
     * @return \Symfony\Component\HttpFoundation\Response Возвращаем ответ
     */
    public function searchChatsAction(Request $request)
    {
        try {
            $version = $request->get(RequestConstant::VERSION_PARAM, RequestConstant::NEW_DEFAULT_VERSION);

            /** @var string $chatId ID чата в котором можем искать */
            $chatId = $request->get(RequestConstant::CHAT_ID_PARAM);

            // получаем из запроса ID пользователя
            $userId = $this->getRequestUserId();

            $chatSearchService = $this->getChatMessageSearchService();

            $chatMessages = $chatSearchService->searchByChatMessage(
                $userId,
                null,
                $chatId,
                true,
                $this->getSkip(),
                $this->getCount()
            );

            $messages = $chatSearchService->chatMessageTransformer->transformAggregations(
                $chatSearchService->getAggregations(),
                ChatMessageMapping::CONTEXT,
                ChatMessageMapping::CONTEXT,
                ChatMessageMapping::LAST_CHAT_MESSAGE
            );

            if (empty($messages)) {
                return $this->_handleViewWithData([]);
            }

            $chatMessages = [];
            $totalMessageCount = 0;
            foreach ($messages[ChatMessageMapping::CONTEXT] as &$chatMessage) {

                $countUnDeleted = $chatSearchService->getCountUnDeleteMessages($userId, $chatMessage['chatId']);

                /**
                 * Проверяем равно ли кол-во сообщений в чате
                 * с кол-ом удаленных, если да - это означаем что чат удалён
                 */
                // if ($countUnDeleted < $chatMessage['messages_count']) {
                if ($countUnDeleted > 0) {
                    $chatMessage['count'] = $chatSearchService->getCountUnreadMessages($userId, $chatMessage['chatId']);
                    $chatMessages[ChatMessageMapping::CONTEXT][] = $chatMessage;
                    $totalMessageCount += $chatMessage['messages_count'];
                }
            }

            if (empty($chatMessages)) {
                return $this->_handleViewWithData([]);
            }

            if (!is_null($version) && (int)$version === RequestConstant::DEFAULT_VERSION) {

                $chatMessages[ChatMessageMapping::CONTEXT] = $chatSearchService->chatMessageTransformer->transform(
                    $chatMessages,
                    ChatMessageMapping::CONTEXT
                );

                $chatList = array_slice(
                    $chatMessages[ChatMessageMapping::CONTEXT],
                    $this->getSkip(),
                    $this->getCount()
                );

                return $this->_handleViewWithData(
                    $chatList,
                    null,
                    !self::INCLUDE_IN_CONTEXT
                );
            }

            return $this->_handleViewWithData(array_merge(
                [
                    'info' => [
                        'totalHits'  => $totalMessageCount,
                        'totalChats' => count($chatMessages[ChatMessageMapping::CONTEXT]),
                    ],
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