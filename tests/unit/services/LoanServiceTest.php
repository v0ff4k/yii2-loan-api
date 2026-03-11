<?php

namespace tests\unit\services;

use PHPUnit\Framework\TestCase;
use app\models\LoanRequest;
use app\services\LoanService;
use Yii;

/**
 * Тесты для LoanService.
 */
class LoanServiceTest extends TestCase
{
    private LoanService $service;

    protected function setUp(): void
    {
        parent::setUp();
        LoanRequest::deleteAll();
        $this->service = new LoanService();
    }

    /**
     * Тест успешной подачи заявки.
     */
    public function testSubmitRequestSuccess(): void
    {
        $result = $this->service->submitRequest(1, 3000, 30);

        $this->assertTrue($result['result']);
        $this->assertArrayHasKey('id', $result);
        $this->assertGreaterThan(0, $result['id']);

        $request = LoanRequest::findOne($result['id']);
        $this->assertNotNull($request);
        $this->assertEquals(1, $request->user_id);
        $this->assertEquals(3000, $request->amount);
        $this->assertEquals(30, $request->term);
        $this->assertEquals(LoanRequest::STATUS_PENDING, $request->status);
    }

    /**
     * Тест отклонения заявки при наличии одобренной.
     */
    public function testSubmitRequestWithExistingApproved(): void
    {
        $existingRequest = new LoanRequest();
        $existingRequest->user_id = 1;
        $existingRequest->amount = 1000;
        $existingRequest->term = 10;
        $existingRequest->status = LoanRequest::STATUS_APPROVED;
        $existingRequest->save();

        $result = $this->service->submitRequest(1, 3000, 30);

        $this->assertFalse($result['result']);
        $this->assertArrayNotHasKey('id', $result);
    }

    /**
     * Тест подачи заявки при наличии declined.
     */
    public function testSubmitRequestWithExistingDeclined(): void
    {
        $existingRequest = new LoanRequest();
        $existingRequest->user_id = 1;
        $existingRequest->amount = 1000;
        $existingRequest->term = 10;
        $existingRequest->status = LoanRequest::STATUS_DECLINED;
        $existingRequest->save();

        $result = $this->service->submitRequest(1, 3000, 30);

        $this->assertTrue($result['result']);
        $this->assertArrayHasKey('id', $result);
    }

    /**
     * Тест процессора: обработка pending заявок.
     */
    public function testProcessRequestsProcessesPending(): void
    {
        $request1 = new LoanRequest();
        $request1->user_id = 1;
        $request1->amount = 1000;
        $request1->term = 10;
        $request1->status = LoanRequest::STATUS_PENDING;
        $request1->save();

        $request2 = new LoanRequest();
        $request2->user_id = 2;
        $request2->amount = 2000;
        $request2->term = 20;
        $request2->status = LoanRequest::STATUS_PENDING;
        $request2->save();

        $result = $this->service->processRequests(0);

        $this->assertTrue($result['result']);

        $pendingCount = LoanRequest::find()
            ->where(['status' => LoanRequest::STATUS_PENDING])
            ->count();

        $this->assertEquals(0, $pendingCount);
    }

    /**
     * Тест процессора: игнорирование non-pending заявок.
     */
    public function testProcessRequestsIgnoresNonPending(): void
    {
        $approved = new LoanRequest();
        $approved->user_id = 1;
        $approved->amount = 1000;
        $approved->term = 10;
        $approved->status = LoanRequest::STATUS_APPROVED;
        $approved->save();

        $declined = new LoanRequest();
        $declined->user_id = 2;
        $declined->amount = 2000;
        $declined->term = 20;
        $declined->status = LoanRequest::STATUS_DECLINED;
        $declined->save();

        $this->service->processRequests(0);

        $approvedAfter = LoanRequest::find()
            ->where(['status' => LoanRequest::STATUS_APPROVED])
            ->count();

        $declinedAfter = LoanRequest::find()
            ->where(['status' => LoanRequest::STATUS_DECLINED])
            ->count();

        $this->assertEquals(1, $approvedAfter);
        $this->assertEquals(1, $declinedAfter);
    }

    /**
     * Тест процессора: один approved на пользователя.
     */
    public function testProcessRequestsOneApprovedPerUser(): void
    {
        // Создаём 5 pending заявок от одного пользователя
        for ($i = 0; $i < 5; $i++) {
            $request = new LoanRequest();
            $request->user_id = 1;
            $request->amount = 1000;
            $request->term = 10;
            $request->status = LoanRequest::STATUS_PENDING;
            $request->save();
        }

        // Запускаем процессор много раз пока не обработаются все
        $iterations = 0;
        do {
            $this->service->processRequests(0);
            $iterations++;
        } while (
            LoanRequest::find()->where(['status' => LoanRequest::STATUS_PENDING])->count() > 0
            && $iterations < 10
        );

        // Должна быть не более одной одобренной заявки (из-за 10% вероятности может быть 0)
        $approvedCount = LoanRequest::find()
            ->where([
                'user_id' => 1,
                'status' => LoanRequest::STATUS_APPROVED
            ])
            ->count();

        $this->assertLessThanOrEqual(1, $approvedCount);

        // Все заявки должны быть обработаны (approved + declined = 5)
        $declinedCount = LoanRequest::find()
            ->where([
                'user_id' => 1,
                'status' => LoanRequest::STATUS_DECLINED
            ])
            ->count();

        $this->assertEquals(5, $approvedCount + $declinedCount);
    }

    /**
     * Тест процессора с пустой очередью.
     */
    public function testProcessRequestsEmptyQueue(): void
    {
        $result = $this->service->processRequests(0);

        $this->assertTrue($result['result']);
    }
}
