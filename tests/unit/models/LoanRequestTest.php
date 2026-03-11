<?php

namespace tests\unit\models;

use PHPUnit\Framework\TestCase;
use app\models\LoanRequest;
use Yii;

class LoanRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        LoanRequest::deleteAll();
    }

    /**
     * Тест валидации обязательных полей.
     */
    public function testValidationRequiredFields()
    {
        $model = new LoanRequest();

        $this->assertFalse($model->validate());

        $this->assertArrayHasKey('user_id', $model->getErrors());
        $this->assertArrayHasKey('amount', $model->getErrors());
        $this->assertArrayHasKey('term', $model->getErrors());
    }

    /**
     * Тест валидации типов данных.
     */
    public function testValidationIntegerTypes()
    {
        $model = new LoanRequest();
        $model->user_id = 'abc';
        $model->amount = 'xyz';
        $model->term = '123abc';

        $this->assertFalse($model->validate());
        $this->assertArrayHasKey('user_id', $model->getErrors());
    }

    /**
     * Тест успешного создания заявки.
     */
    public function testCreateValidRequest()
    {
        $model = new LoanRequest();
        $model->user_id = 1;
        $model->amount = 3000;
        $model->term = 30;
        $model->status = LoanRequest::STATUS_PENDING;

        $this->assertTrue($model->validate());
        $this->assertTrue($model->save());
        $this->assertNotNull($model->id);
        $this->assertEquals(LoanRequest::STATUS_PENDING, $model->status);
    }

    /**
     * Тест статуса по умолчанию.
     */
    public function testDefaultStatus()
    {
        $model = new LoanRequest();
        $model->user_id = 1;
        $model->amount = 1000;
        $model->term = 15;
        $model->save();

        $this->assertEquals(LoanRequest::STATUS_PENDING, $model->status);
    }

    /**
     * Тест констант статусов.
     */
    public function testStatusConstants()
    {
        $this->assertEquals('pending', LoanRequest::STATUS_PENDING);
        $this->assertEquals('approved', LoanRequest::STATUS_APPROVED);
        $this->assertEquals('declined', LoanRequest::STATUS_DECLINED);
    }

    /**
     * Тест граничного значения: amount = 0.
     */
    public function testZeroAmount()
    {
        $model = new LoanRequest();
        $model->user_id = 1;
        $model->amount = 0;
        $model->term = 30;

        $this->assertTrue($model->validate());
        $this->assertTrue($model->save());
    }

    /**
     * Тест отрицательного amount.
     */
    public function testNegativeAmount()
    {
        $model = new LoanRequest();
        $model->user_id = 1;
        $model->amount = -100;
        $model->term = 30;

        $this->assertTrue($model->validate());
        $this->assertTrue($model->save());
    }

    /**
     * Тест отрицательного term.
     */
    public function testNegativeTerm()
    {
        $model = new LoanRequest();
        $model->user_id = 1;
        $model->amount = 1000;
        $model->term = -5;

        $this->assertTrue($model->validate());
        $this->assertTrue($model->save());
    }

    /**
     * Тест больших значений.
     */
    public function testLargeValues()
    {
        $model = new LoanRequest();
        $model->user_id = 999999;
        $model->amount = 999999999;
        $model->term = 9999;

        $this->assertTrue($model->validate());
        $this->assertTrue($model->save());
    }

    /**
     * Тест null значений.
     */
    public function testNullValues()
    {
        $model = new LoanRequest();
        $model->user_id = null;
        $model->amount = null;
        $model->term = null;

        $this->assertFalse($model->validate());
        $this->assertArrayHasKey('user_id', $model->getErrors());
        $this->assertArrayHasKey('amount', $model->getErrors());
        $this->assertArrayHasKey('term', $model->getErrors());
    }
}
