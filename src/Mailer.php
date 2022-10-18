<?php

namespace MarketforceInfo\SendGrid;

use SendGrid;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\mail\BaseMailer;

class Mailer extends BaseMailer
{
    public const LOGNAME = 'SendGrid Mailer';

    /**
     * @var string the default class name of the new message instances created by [[createMessage()]]
     */
    public $messageClass = Message::class;

    /**
     * @var string the directory where the email messages are saved when [[useFileTransport]] is true.
     */
    public $fileTransportPath = '@runtime/mail';

    /**
     * @var string the api key for the sendgrid api
     */
    public string $apiKey;

    /**
     * @var array a list of options for the sendgrid api
     */
    public array $options = [];

    private SendGrid $sendGrid;

    /**
     * @var array Raw response data from client
     */
    private array $rawResponses = [];

    /**
     * @var array List of errors from the client
     */
    private array $errors = [];

    /**
     * Get SendGrid instance
     *
     * A SendGrid instance is created using `createSendGrid()` if it hasn't
     * already been instantiated.
     *
     * @return SendGrid instance
     * @throws InvalidConfigException
     */
    public function getSendGrid(): SendGrid
    {
        if (!isset($this->sendGrid)) {
            $this->sendGrid = $this->createSendGrid();
        }

        return $this->sendGrid;
    }

    /**
     * Create a new Batch ID from SendGrid
     *
     * @return string|false New batch id from SendGrid
     */
    public function createBatchId()
    {
        try {
            $response = $this->getSendGrid()->client->mail()->batch()->post();
            if ($response->statusCode() !== 201) {
                return false;
            }
            $decoded = json_decode($response->body(), false, 512, JSON_THROW_ON_ERROR);
            $batchId = $decoded->batch_id;
            return $batchId ?? false;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * Create SendGrid instance
     *
     * @return SendGrid instance
     * @throws InvalidConfigException
     */
    public function createSendGrid(): SendGrid
    {
        if (!isset($this->apiKey)) {
            throw new InvalidConfigException('SendGrid API Key is required!');
        }

        return new SendGrid($this->apiKey, $this->options);
    }

    /**
     * @return array Get the array of raw JSON responses.
     */
    public function getRawResponses(): array
    {
        return $this->rawResponses;
    }

    public function addRawResponse(array $response): self
    {
        $this->rawResponses[] = Json::encode($response);
        return $this;
    }

    /**
     * @return array Get array of errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(string $message): self
    {
        $this->errors[] = $message;
        return $this;
    }

    public function parseErrorCode($code): string
    {
        static $key = [
            200 => 'Your message is valid, but it is not queued to be delivered. (Sandbox)',
            202 => 'Your message is both valid, and queued to be delivered.',
            400 => 'Bad Request!',
            401 => 'You do not have authorization to make the request! Your API Key is probably missing or incorrect!',
            403 => 'Forbidden!',
            404 => 'The resource you tried to locate could not be found or does not exist.',
            405 => 'Method Not Allowed!',
            413 => 'The JSON payload you have included in your request is too large.',
            415 => 'Unsupported Media Type',
            429 => 'The number of requests you have made exceeds SendGrid’s rate limitations.',
            500 => 'An error occurred on a SendGrid server.',
            503 => 'The SendGrid v3 Web API is not available.',
        ];
        return $key[$code] ?? "{$code}: An unknown error was encountered!";
    }

    public function sendMessage($message): bool
    {
        try {
            $payload = $message->buildMessage();
            if (!$payload) {
                throw new \Exception('Error building message. Unable to send!');
            }

            $response = $this->getSendGrid()->client->mail()->send()->post($payload);

            $formatResponse = [
                'code' => $response->statusCode(),
                'headers' => $response->headers(),
                'body' => $response->body(),
            ];
            $this->addRawResponse($formatResponse);

            if (($response->statusCode() !== 202) && ($response->statusCode() !== 200)) {
                throw new \Exception($this->parseErrorCode($response->statusCode()));
            }

            return true;
        } catch (\Exception $e) {
            Yii::error($e->getMessage(), self::LOGNAME);
            $this->addError($e->getMessage());

            return false;
        }
    }
}
