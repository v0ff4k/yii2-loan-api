<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use app\models\LoanRequest;

/**
 * Class LoanController
 * Реализует логику API.
 * POST /requests: Использует CTE (Common Table Expression) для проверки наличия одобренных заявок в одном запросе к БД перед вставкой.
 * GET /processor: Реализует задержку, рандом и обработку статусов.
 *
 * @package app\controllers
 */
class LoanController extends Controller
{
    public function init()
    {
        parent::init();
        Yii::$app->response->format = Response::FORMAT_JSON;
    }

    /**
     * POST /requests
     * Подача заявки на займ
     */
    public function actionRequests()
    {

        if (Yii::$app->request->method !== 'POST') {
            return $this->asJson(['result' => false], 405);
        }

        $post = Yii::$app->request->post();

        if (
            !isset($post['user_id'], $post['amount'], $post['term']) ||
            !is_numeric($post['user_id']) ||
            !is_numeric($post['amount']) ||
            !is_numeric($post['term'])
        ) {
            // Валидация входных данных

            return $this->asJson(
                ['result' => false],
                400
            );
        }

        $userId = (int)$post['user_id'];
        $amount = (int)$post['amount'];
        $term = (int)$post['term'];

        // ТРЕБОВАНИЕ: Использование CTE для проверки бизнес-правила
        // Проверяем, есть ли у пользователя уже одобренная заявка
        $cteQuery = "
            WITH user_approved_check AS (
                SELECT COUNT(*) as cnt 
                FROM loan_requests 
                WHERE user_id = :userId AND status = :status
            )
            SELECT cnt FROM user_approved_check
        ";

        $command = Yii::$app->db->createCommand($cteQuery);
        $command->bindValue(':userId', $userId);
        $command->bindValue(':status', LoanRequest::STATUS_APPROVED);

        $result = $command->queryScalar();

        if ($result > 0) {
            // У пользователя уже есть одобренная заявка
            return $this->asJson(['result' => false], 400);
        }

        // Создание заявки
        $model = new LoanRequest();
        $model->user_id = $userId;
        $model->amount = $amount;
        $model->term = $term;
        $model->status = LoanRequest::STATUS_PENDING;

        if ($model->save()) {
            return $this->asJson(['result' => true, 'id' => $model->id], 201);
        }

        // Обработка ошибок валидации модели или БД (например, нарушение уникального индекса)
        return $this->asJson(['result' => false], 400);
    }

    /**
     * GET /processor
     * Обработка заявок
     */
    public function actionProcessor()
    {
        if (Yii::$app->request->method !== 'GET') {
            return $this->asJson(['result' => false], 405);
        }

        $delay = Yii::$app->request->get('delay', 0);
        $delay = max(0, (int)$delay);

        // Получаем все pending заявки
        // Для демонстрации работы с БД можно было бы использовать оконные функции для ранжирования,
        // но для простой выборки достаточно стандартного запроса.
        $requests = LoanRequest::find()
            ->where(['status' => LoanRequest::STATUS_PENDING])
            ->all();

        foreach ($requests as $request) {
            // Эмуляция времени принятия решения
            sleep($delay);

            // Рандомное решение: 10% шанс одобрения
            $isApproved = (mt_rand(1, 100) <= 10);
            if ($isApproved) {
                $request->status = LoanRequest::STATUS_APPROVED;
            } else {
                $request->status = LoanRequest::STATUS_DECLINED;
            }

            // Попытка сохранения.
            // Если сработает уникальный индекс (у пользователя уже есть approved),
            // save() вернет false. В реальном приложении тут нужна обработка исключения,
            // но по ТЗ нам нужно просто установить статус.
            // Если не удалось одобрить из-за конкуренции -> считаем declined.
            if (!$request->save()) {
                // Если не сохранилось (конфликт индекса), значит кто-то другой уже одобрил этому юзеру
                // Принудительно ставим declined, чтобы заявка не висела в pending
                $request->status = LoanRequest::STATUS_DECLINED;
                $request->save(false);
            }
        }

        return $this->asJson(['result' => true], 200);
    }

    public function asJson($data, $statusCode = 200)
    {
        Yii::$app->response->statusCode = $statusCode;

        return $data;
    }
}