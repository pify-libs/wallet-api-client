<?php

namespace Pify\WalletApiClient;

class WalletApiClient
{
    private $baseUrl = 'https://pify.cc';
    private $apiToken;
    private $timeout = 30;
    private $lastError;
    private $lastResponse;

    /**
     * Конструктор клиента
     *
     * @param string $apiToken API токен от pify.cc
     */
    public function __construct($apiToken)
    {
        $this->apiToken = $apiToken;
    }

    /**
     * Установка базового URL API
     *
     * @param string $url Базовый URL
     * @return self
     */
    public function setBaseUrl($url)
    {
        $this->baseUrl = rtrim($url, '/');
        return $this;
    }

    /**
     * Установка таймаута запросов
     *
     * @param int $timeout Таймаут в секундах
     * @return self
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Получение последней ошибки
     *
     * @return string|null
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Получение полного ответа последнего запроса
     *
     * @return array|null
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Получение баланса всех кошельков пользователя
     *
     * @return array|false
     */
    public function getBalance()
    {
        return $this->makeRequest('/api/balance', 'GET');
    }

    /**
     * Получение истории операций
     *
     * @param array $params Параметры запроса
     *   - wallet_id (int) ID кошелька (опционально)
     *   - page (int) Номер страницы
     *   - page_size (int) Размер страницы
     *   - filters (array) Фильтры
     * @return array|false
     */
    public function getHistory($params = [])
    {
        return $this->makeRequest('/api/history', 'GET', $params);
    }

    /**
     * Перевод между кошельками
     *
     * @param int $fromWalletId ID кошелька отправителя
     * @param string $toIdentifier ID кошелька или адрес получателя
     * @param float $amount Сумма перевода
     * @param string $comment Комментарий к переводу
     * @return array|false
     */
    public function transfer($fromWalletId, $toIdentifier, $amount, $comment = '')
    {
        $data = [
            'from_wallet_id' => $fromWalletId,
            'to_identifier' => $toIdentifier,
            'amount' => (float)$amount,
            'comment' => $comment
        ];

        return $this->makeRequest('/api/transfer', 'POST', $data);
    }

    /**
     * Перевод на внешний адрес
     *
     * @param int $fromWalletId ID кошелька отправителя
     * @param string $toAddress Внешний адрес получателя
     * @param float $amount Сумма перевода
     * @param string $comment Комментарий к переводу
     * @return array|false
     */
    public function transferExternal($fromWalletId, $toAddress, $amount, $comment = '')
    {
        $data = [
            'from_wallet_id' => $fromWalletId,
            'to_address' => $toAddress,
            'amount' => (float)$amount,
            'comment' => $comment
        ];

        return $this->makeRequest('/api/transfer-external', 'POST', $data);
    }

    /**
     * Проверка возможности перевода
     *
     * @param int $fromWalletId ID кошелька отправителя
     * @param float $amount Сумма перевода
     * @return array|false
     */
    public function checkTransfer($fromWalletId, $amount)
    {
        $data = [
            'from_wallet_id' => $fromWalletId,
            'amount' => (float)$amount
        ];

        return $this->makeRequest('/api/check-transfer', 'POST', $data);
    }

    /**
     * Получение статистики по операциям
     *
     * @param string $period Период: day, week, month, year
     * @return array|false
     */
    public function getStatistics($period = 'month')
    {
        return $this->makeRequest('/api/statistics', 'GET', ['period' => $period]);
    }

    /**
     * Получение информации о конкретном кошельке
     *
     * @param int $walletId ID кошелька
     * @return array|false
     */
    public function getWalletInfo($walletId)
    {
        return $this->makeRequest('/api/wallet-info', 'GET', ['wallet_id' => $walletId]);
    }

    /**
     * Базовый метод для выполнения HTTP запросов
     *
     * @param string $endpoint API endpoint
     * @param string $method HTTP метод
     * @param array $data Данные запроса
     * @return array|false
     */
    private function makeRequest($endpoint, $method = 'GET', $data = [])
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();

        $headers = [
            'Authorization: Bearer ' . $this->apiToken,
            'Content-Type: application/json',
            'User-Agent: Pify-WalletApiClient/1.0'
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'GET' && !empty($data)) {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        $this->lastResponse = [
            'http_code' => $httpCode,
            'response' => $response,
            'error' => $error
        ];

        curl_close($ch);

        if ($error) {
            $this->lastError = "cURL Error: " . $error;
            return false;
        }

        if ($httpCode !== 200) {
            $this->lastError = "HTTP Error: " . $httpCode;
            return false;
        }

        $decodedResponse = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->lastError = "JSON decode error: " . json_last_error_msg();
            return false;
        }

        if (isset($decodedResponse['success']) && !$decodedResponse['success']) {
            $this->lastError = $decodedResponse['error'] ?? 'Unknown API error';
            if (isset($decodedResponse['message'])) {
                $this->lastError .= ': ' . $decodedResponse['message'];
            }
            return false;
        }

        return $decodedResponse;
    }
}
