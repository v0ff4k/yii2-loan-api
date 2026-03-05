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
        // Очистка таблицы перед каждым тестом
        LoanRequest::deleteAll();
    }

    /**
     * Тест валидации обязательных полей
     */
    public function testValidationRequiredFields()
    {
        $model = new LoanRequest();
        
        // Пустая модель не должна валидироваться
        $this->assertFalse($model->validate());
        
        // Проверка ошибок валидации
        $this->assertArrayHasKey('user_id', $model->getErrors());
        $this->assertArrayHasKey('amount', $model->getErrors());
        $this->assertArrayHasKey('term', $model->getErrors());
    }

    /**
     * Тест валидации типов данных
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
     * Тест успешного создания заявки
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
     * Тест статуса по умолчанию
     */
    public function testDefaultStatus()
    {
        $model = new LoanRequest();
        $model->user_id = 1;
        $model->amount = 1000;
        $model->term = 15;
        
        // Статус должен устанавливаться по умолчанию
        $model->save();
        $this->assertEquals(LoanRequest::STATUS_PENDING, $model->status);
    }

    /**
     * Тест констант статусов
     */
    public function testStatusConstants()
    {
        $this->assertEquals('pending', LoanRequest::STATUS_PENDING);
        $this->assertEquals('approved', LoanRequest::STATUS_APPROVED);
        $this->assertEquals('declined', LoanRequest::STATUS_DECLINED);
    }
}
