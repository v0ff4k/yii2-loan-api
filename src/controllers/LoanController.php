<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\services\LoanService;

/**
 * Контроллер для обработки заявок на займ.
 *
 * Является тонкой обёрткой над LoanService,
 * делегирует всю бизнес-логику сервисному слою.
 */
class LoanController extends Controller
{
    private LoanService $loanService;

    public function __construct($id, $module, LoanService $loanService, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->loanService = $loanService;
    }

    public function init()
    {
        parent::init();
        Yii::$app->response->format = Response::FORMAT_JSON;
    }

    /**
     * POST /requests
     * Подача заявки на займ.
     *
     * @return array
     */
    public function actionRequests()
    {
        $post = Yii::$app->request->post();

        $userId = $post['user_id'] ?? null;
        $amount = $post['amount'] ?? null;
        $term = $post['term'] ?? null;

        // Валидация входных данных
        if (
            $userId === null || $amount === null || $term === null ||
            !is_numeric($userId) || !is_numeric($amount) || !is_numeric($term)
        ) {
            Yii::$app->response->statusCode = 400;
            return ['result' => false];
        }

        $result = $this->loanService->submitRequest(
            (int)$userId,
            (int)$amount,
            (int)$term
        );

        if ($result['result']) {
            Yii::$app->response->statusCode = 201;
        } else {
            Yii::$app->response->statusCode = 400;
        }

        return $result;
    }

    /**
     * GET /processor
     * Обработка заявок.
     *
     * @return array
     */
    public function actionProcessor()
    {
        $delay = max(0, (int)(Yii::$app->request->get('delay', 0)));

        $result = $this->loanService->processRequests($delay);

        Yii::$app->response->statusCode = 200;

        return $result;
    }
}