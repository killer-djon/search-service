<?php

namespace RP\SearchBundle\Tests\SearchController;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Created by PhpStorm.
 * User: eleshanu
 * Date: 10.07.17
 * Time: 15:58
 */
class SearchNewsFeedControllerTest extends WebTestCase
{
    /**
     * @const string
     */
    const SERVICE_BASE_URL = '//app_dev.php/api/search/v1/json/newsfeed/';

    /**
     * Токен по умолчанию от имени которого
     * мы делаем запросы к сервису
     *
     * @const string
     */
    const TOKEN_ID = '539097f774f01a57b7826e4e';

    /**
     * Кол-во данных при одиночном резульатет/запросе
     * @var int
     */
    const SINGLE_VALUE = 1;

    /**
     * В случае если нам надо получить больше результатов
     * @var int
     */
    const MULTIPLE_VALUE = 10;

    /**
     * Объект клиенрта осуществляющий запросы на сервак
     * для получения данных которые нужно ассертить
     *
     * @var Client
     */
    private $client;

    /**
     * Ответа от сервака
     * переведенный в формат array для дальнейших работ
     *
     * @var array
     */
    private $jsonResponse;

    /**
     * Контейнер с содержимым ответа
     * @var array
     */
    private $resultData;


    /**
     * Общая информация по ответу
     *
     * @var array
     */
    private $resultInfo;

    /**
     * данные ответа
     * по постраничной навигации
     *
     * @var array
     */
    private $resultPagination;


    /**
     * Набор непосредственно самих данных в ответе
     *
     * @var array
     */
    private $resultItems;

    /**
     * DonNode объект
     *
     * @var Crawler
     */
    private $crawler;

    /**
     * Конструктор функциаонального теста
     */
    public function setUp()
    {
        $this->client = static::createClient([
            'environment' => 'test',
            'debug'       => true,
        ]);
    }

    /**
     * Значения параметров запроса по умолчанию
     * которые нужны для запросов на сервак
     *
     * @param int $resultCnt Кол-во запрашиваемых данных
     * @return array
     */
    private function getDefaultRequestOptions($resultCnt = self::SINGLE_VALUE)
    {
        return [
            'skip'  => 0,
            'count' => $resultCnt
        ];
    }

    /**
     * Получаем заголовки по умолчанию
     * практически для каждого запроса
     * который мы отправляем на сервер через контроллеры
     *
     * @return array
     */
    private function getDefaultHeaders()
    {
        return [
            'HTTP_token_id' => self::TOKEN_ID
        ];
    }

    /**
     * метод запроса данных с сервиса
     *
     * @param array $requestOptions Параметры запроса
     * @param array $requestHeaders Заголовки запроса
     */
    private function getUserEvents($requestOptions = [], $requestHeaders = [])
    {
        $this->crawler = $this->client->request(
            'GET',
            self::SERVICE_BASE_URL . 'userevents/list',
            $requestOptions,
            [],
            $requestHeaders
        );
    }

    /**
     * Общий набор тестов для проверки исходного ответа
     * приходящий от сервака в json формате
     */
    private function assertTestJsonResponse()
    {

        // проверяем код ответа сервера - 200 это верный ответ сервера
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // проверяем сначало исходный ответ в виде строки json (пустота, формат ...)
        $this->assertNotEmpty($this->client->getResponse()->getContent());
        $this->assertJson($this->client->getResponse()->getContent());

        $this->jsonResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->resultData = $this->jsonResponse['data']['result'];
        $this->resultInfo = $this->resultData['info'];
        $this->resultPagination = $this->resultData['pagination'];
        $this->resultItems = $this->resultData['items'];

        //далее распаковываем json ответ в массив и смотрим результат
        $this->assertArrayHasKey('data', $this->jsonResponse);
        $this->assertArrayHasKey('result', $this->jsonResponse['data']);
    }



    /**
     * Тест-кейс для ленты общей
     * необходимо проверить есть ли лента и приходит ли ответ
     * при поисковом запросе
     *
     * Позитивный сценарий
     */
    public function testNewsFeedUserEvents()
    {
        $this->getUserEvents(
            $this->getDefaultRequestOptions(self::MULTIPLE_VALUE),
            $this->getDefaultHeaders()
        );

        // общие тест кейсы проверки json ответа
        $this->assertTestJsonResponse();

        // проверки содержимого объекта ответа
        $this->assertGreaterThan(0, $this->resultInfo['totalHits']);
        $this->assertNotEmpty($this->resultInfo);
        $this->assertNotEmpty($this->resultPagination);
        $this->assertNotEmpty($this->resultItems);
        $this->assertEquals(self::MULTIPLE_VALUE, $this->resultPagination['limit']);
        $this->assertGreaterThan(0, $this->resultItems);
        if( $this->resultInfo['totalHits'] > self::MULTIPLE_VALUE )
        {
            $this->assertCount(self::MULTIPLE_VALUE, $this->resultItems);
        }
    }

    /**
     * Тест-кейс для ленты общей
     * необходимо проверить есть ли лента и приходит ли ответ
     * при поисковом запросе
     *
     * Негативный сценарий
     */
    public function testNewsFeedUserEventsFailure()
    {
        $this->getUserEvents(
            $this->getDefaultRequestOptions(self::MULTIPLE_VALUE)
        );

        $this->assertEquals(500, $this->client->getResponse()->getStatusCode());
        // проверяем сначало исходный ответ в виде строки json (пустота, формат ...)
        $this->assertNotEmpty($this->client->getResponse()->getContent());
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertContains('authentication is required', $this->client->getResponse()->getContent());
    }
}