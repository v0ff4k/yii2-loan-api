<?php

namespace app\services;

use Yii;
use yii\db\Exception;
use yii\db\IntegrityException;
use yii\db\StaleObjectException;
use app\models\LoanRequest;

/**
 * Сервис для обработки заявок на займ.
 *
 * Инкапсулирует бизнес-логику подачи и обработки заявок,
 * включая проверку бизнес-правил и взаимодействие с БД.
 */
class LoanService
{
    /**
     * Подаёт новую заявку на займ.
     *
     * @param int $userId идентификатор пользователя
     * @param int $amount сумма займа
     * @param int $term срок займа в днях
     * @return array результат операции ['result' => bool, 'id' => int|null]
     */
    public function submitRequest(int $userId, int $amount, int $term): array
    {
        // Проверяем наличие одобренных заявок у пользователя
        if ($this->hasApprovedRequest($userId)) {
            return ['result' => false];
        }

        // Создаём новую заявку
        $request = new LoanRequest();
        $request->user_id = $userId;
        $request->amount = $amount;
        $request->term = $term;
        $request->status = LoanRequest::STATUS_PENDING;

        if ($request->save()) {
            return ['result' => true, 'id' => $request->id];
        }

        return ['result' => false];
    }

    /**
     * Обрабатывает все ожидающие заявки.
     *
     * Для каждой pending-заявки эмулируется задержка принятия решения,
     * после чего статус меняется на approved (10% вероятность) или declined.
     *
     * @param int $delay задержка в секундах для эмуляции принятия решения
     * @return array результат операции ['result' => bool]
     */
    public function processRequests(int $delay): array
    {
        $requests = LoanRequest::find()
            ->where(['status' => LoanRequest::STATUS_PENDING])
            ->all();

        foreach ($requests as $request) {
            // Эмуляция времени принятия решения
            if ($delay > 0) {
                sleep($delay);
            }

            // Рандомное решение: 10% шанс одобрения
            $isApproved = (mt_rand(1, 100) <= 10);

            try {
                if ($isApproved) {
                    $request->status = LoanRequest::STATUS_APPROVED;
                    if (!$request->save()) {
                        // Если не удалось сохранить (конфликт уникального индекса),
                        // значит у пользователя уже есть одобренная заявка
                        $request->status = LoanRequest::STATUS_DECLINED;
                        $request->save(false);
                        Yii::info(sprintf(
                            'Заявка #%d отклонена: у пользователя #%d уже есть одобренная заявка',
                            $request->id,
                            $request->user_id
                        ));
                    } else {
                        Yii::info(sprintf(
                            'Заявка #%d одобрена (пользователь #%d)',
                            $request->id,
                            $request->user_id
                        ));
                    }
                } else {
                    $request->status = LoanRequest::STATUS_DECLINED;
                    $request->save(false);
                    Yii::info(sprintf(
                        'Заявка #%d отклонена случайным выбором (пользователь #%d)',
                        $request->id,
                        $request->user_id
                    ));
                }
            } catch (IntegrityException $e) {
                // Нарушение уникального индекса - у пользователя уже есть approved
                Yii::info(sprintf(
                    'Заявка #%d отклонена: нарушение уникальности (пользователь #%d)',
                    $request->id,
                    $request->user_id
                ));
                $request->status = LoanRequest::STATUS_DECLINED;
                $request->save(false);
            } catch (StaleObjectException $e) {
                Yii::error(sprintf(
                    'Ошибка при обработке заявки #%d: %s',
                    $request->id,
                    $e->getMessage()
                ));
                $request->status = LoanRequest::STATUS_DECLINED;
                $request->save(false);
            } catch (Exception $e) {
                Yii::error(sprintf(
                    'Ошибка БД при обработке заявки #%d: %s',
                    $request->id,
                    $e->getMessage()
                ));
                throw $e;
            }
        }

        return ['result' => true];
    }

    /**
     * Проверяет наличие одобренных заявок у пользователя.
     *
     * @param int $userId идентификатор пользователя
     * @return bool true если есть одобренные заявки
     */
    private function hasApprovedRequest(int $userId): bool
    {
        $count = LoanRequest::find()
            ->where([
                'user_id' => $userId,
                'status' => LoanRequest::STATUS_APPROVED
            ])
            ->count();

        return $count > 0;
    }
}
