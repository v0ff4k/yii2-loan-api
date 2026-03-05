<?php

use app\models\LoanRequest;
use yii\db\Migration;

class m231027_100000_create_loan_requests_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('loan_requests', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'amount' => $this->integer()->notNull(),
            'term' => $this->integer()->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue(LoanRequest::STATUS_PENDING), // pending, approved, declined
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Индекс для быстрого поиска pending
        $this->createIndex('idx_loan_requests_status', 'loan_requests', 'status');

        // Критически важное ограничение: один approved на пользователя
        // Используем частичный индекс PostgreSQL
        $this->execute("CREATE UNIQUE INDEX unique_approved_per_user ON loan_requests (user_id) WHERE status = 'approved'");
    }

    public function safeDown()
    {
        $this->dropTable('loan_requests');
    }
}