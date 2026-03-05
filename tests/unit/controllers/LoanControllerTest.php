<?php

namespace tests\unit\controllers;

use Yii;
use PHPUnit\Framework\TestCase;
use app\models\LoanRequest;
use yii\web\NotFoundHttpException;

class LoanControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        LoanRequest::deleteAll();

        // Сброс состояния ответа перед каждым тестом
        Yii::$app->response->statusCode = 200;
        Yii::$app->response->data = null;

        // Очистка суперглобалов
        $_POST = [];
        $_GET = [];
        $_FILES = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        //  ПЕРЕИНИЦИАЛИЗАЦИЯ REQUEST (вместо refresh())
        // Создаём новый объект request чтобы сбросить кэш $_POST
        Yii::$app->set('request', [
            'class' => 'yii\web\Request',
            'enableCsrfValidation' => false,
        ]);

        Yii::$app->request->enableCsrfValidation = false;
    }

    /**
     * Тест успешной подачи заявки
     */
    public function testCreateRequestSuccess()
    {
        $postData = [
            'user_id' => 1,
            'amount' => 3000,
            'term' => 30
        ];

        // Эмуляция POST запроса
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $postData;

        $controller = new \app\controllers\LoanController('loan', Yii::$app);
        $response = $controller->runAction('requests');

        $this->assertEquals(201, Yii::$app->response->statusCode);
        $this->assertTrue($response['result']);
        $this->assertArrayHasKey('id', $response);
        $this->assertGreaterThan(0, $response['id']);

        // Проверка записи в БД
        $request = LoanRequest::findOne($response['id']);
        $this->assertNotNull($request);
        $this->assertEquals(1, $request->user_id);
        $this->assertEquals(3000, $request->amount);
        $this->assertEquals(LoanRequest::STATUS_PENDING, $request->status);
    }

    /**
     * Тест отклонения заявки при отсутствии обязательных полей
     */
    public function testCreateRequestMissingFields()
    {
        //  Добавить очистку перед тестом
        $_POST = [];
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $postData = [
            'user_id' => 1
            // amount и term отсутствуют
        ];

        $_POST = $postData;

        //  Переинициализируем request после изменения $_POST
        Yii::$app->set('request', [
            'class' => 'yii\web\Request',
            'enableCsrfValidation' => false,
        ]);

        $controller = new \app\controllers\LoanController('loan', Yii::$app);
        $response = $controller->runAction('requests');

        $this->assertEquals(400, Yii::$app->response->statusCode);
        $this->assertFalse($response['result']);
    }

    /**
     * Тест отклонения заявки при некорректных типах данных
     */
    public function testCreateRequestInvalidTypes()
    {
        //  Добавить очистку перед тестом
        $_POST = [];
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $postData = [
            'user_id' => 'invalid',
            'amount' => 'invalid',
            'term' => 'invalid'
        ];

        $_POST = $postData;

        Yii::$app->set('request', [
            'class' => 'yii\web\Request',
            'enableCsrfValidation' => false,
        ]);

        $controller = new \app\controllers\LoanController('loan', Yii::$app);
        $response = $controller->runAction('requests');

        $this->assertEquals(400, Yii::$app->response->statusCode);
        $this->assertFalse($response['result']);
    }

    /**
     * Тест отклонения заявки если у пользователя уже есть одобренная
     */
    public function testCreateRequestWithExistingApproved()
    {
        // Создаем одобренную заявку
        $existingRequest = new LoanRequest();
        $existingRequest->user_id = 1;
        $existingRequest->amount = 1000;
        $existingRequest->term = 10;
        $existingRequest->status = LoanRequest::STATUS_APPROVED;
        $existingRequest->save();

        // Пытаемся создать новую
        $postData = [
            'user_id' => 1,
            'amount' => 3000,
            'term' => 30
        ];

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $postData;

        $controller = new \app\controllers\LoanController('loan', Yii::$app);
        $response = $controller->runAction('requests');

        $this->assertEquals(400, Yii::$app->response->statusCode);
        $this->assertFalse($response['result']);
    }

    /**
     * Тест успешной подачи второй заявки если первая declined
     */
    public function testCreateRequestWithExistingDeclined()
    {
        // Создаем отклоненную заявку
        $existingRequest = new LoanRequest();
        $existingRequest->user_id = 1;
        $existingRequest->amount = 1000;
        $existingRequest->term = 10;
        $existingRequest->status = LoanRequest::STATUS_DECLINED;
        $existingRequest->save();

        // Пытаемся создать новую
        $postData = [
            'user_id' => 1,
            'amount' => 3000,
            'term' => 30
        ];

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $postData;

        $controller = new \app\controllers\LoanController('loan', Yii::$app);
        $response = $controller->runAction('requests');

        $this->assertEquals(201, Yii::$app->response->statusCode);
        $this->assertTrue($response['result']);
    }

    /**
     * Тест процессора заявок
     */
    public function testProcessorEndpoint()
    {
        // Создаем несколько pending заявок
        for ($i = 1; $i <= 3; $i++) {
            $request = new LoanRequest();
            $request->user_id = $i;
            $request->amount = 1000 * $i;
            $request->term = 10;
            $request->status = LoanRequest::STATUS_PENDING;
            $request->save();
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['delay' => 0];

        $controller = new \app\controllers\LoanController('loan', Yii::$app);
        $response = $controller->runAction('processor');

        $this->assertEquals(200, Yii::$app->response->statusCode);
        $this->assertTrue($response['result']);

        // Проверяем что все заявки обработаны (статус изменился)
        $pendingCount = LoanRequest::find()
            ->where(['status' => LoanRequest::STATUS_PENDING])
            ->count();
        
        $this->assertEquals(0, $pendingCount);
    }

    /**
     * Тест процессора с пустой очередью
     */
    public function testProcessorWithNoPendingRequests()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['delay' => 0];

        $controller = new \app\controllers\LoanController('loan', Yii::$app);
        $response = $controller->runAction('processor');

        $this->assertEquals(200, Yii::$app->response->statusCode);
        $this->assertTrue($response['result']);
    }

    /**
     * Тест что процессор не обрабатывает approved/declined заявки
     */
    public function testProcessorIgnoresNonPendingRequests()
    {
        // Создаем заявки разных статусов
        $request1 = new LoanRequest();
        $request1->user_id = 1;
        $request1->amount = 1000;
        $request1->term = 10;
        $request1->status = LoanRequest::STATUS_APPROVED;
        $request1->save();

        $request2 = new LoanRequest();
        $request2->user_id = 2;
        $request2->amount = 2000;
        $request2->term = 20;
        $request2->status = LoanRequest::STATUS_DECLINED;
        $request2->save();

        $approvedBefore = LoanRequest::find()
            ->where(['status' => LoanRequest::STATUS_APPROVED])
            ->count();
        
        $declinedBefore = LoanRequest::find()
            ->where(['status' => LoanRequest::STATUS_DECLINED])
            ->count();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['delay' => 0];

        $controller = new \app\controllers\LoanController('loan', Yii::$app);
        $controller->runAction('processor');

        // Статусы не должны измениться
        $approvedAfter = LoanRequest::find()
            ->where(['status' => LoanRequest::STATUS_APPROVED])
            ->count();
        
        $declinedAfter = LoanRequest::find()
            ->where(['status' => LoanRequest::STATUS_DECLINED])
            ->count();

        $this->assertEquals($approvedBefore, $approvedAfter);
        $this->assertEquals($declinedBefore, $declinedAfter);
    }
}
