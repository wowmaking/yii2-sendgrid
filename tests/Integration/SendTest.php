<?php

namespace MarketforceInfo\SendGrid\Tests\Integration;

use MarketforceInfo\SendGrid\Mailer;
use MarketforceInfo\SendGrid\Message;
use MarketforceInfo\SendGrid\Tests\ResponseHandler;
use PHPUnit\Framework\TestCase;
use SendGrid\Client as SendGridClient;
use SendGrid\Mail\Mail;

/**
 * @covers \MarketforceInfo\SendGrid\Mailer
 * @covers \MarketforceInfo\SendGrid\Message
 */
class SendTest extends TestCase
{
    private Mailer $mailer;

    private ResponseHandler $responseHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailer = new Mailer();
        $this->mailer->apiKey = 'ABC123';

        $client = $this->createStub(SendGridClient::class);
        $this->mailer->getSendGrid()->client = $client;
        $this->responseHandler = new ResponseHandler($client);
    }

    public function testInvalidMessageBuild()
    {
        $message = $this->createStub(Message::class);
        $message->method('buildMessage')->willReturn(null);

        self::assertFalse($this->mailer->send($message));

        $errors = $this->mailer->getErrors();
        self::assertNotEmpty($errors);
        self::assertStringContainsString('Error building message', current($errors));
    }

    public function testInvalidResponse()
    {
        $message = $this->createStub(Message::class);
        $message->method('buildMessage')->willReturn(new Mail());
        $this->responseHandler->respondWith(500, 'Internal Error');

        self::assertFalse($this->mailer->send($message));

        $errors = $this->mailer->getErrors();
        self::assertNotEmpty($errors);
        self::assertStringContainsString('An error occurred', current($errors));
    }

    public function testSendsSuccessfully()
    {
        $message = $this->createStub(Message::class);
        $message->method('buildMessage')->willReturn(new Mail());
        $this->responseHandler->respondWith(202, '');

        self::assertTrue($this->mailer->send($message));

        $errors = $this->mailer->getErrors();
        self::assertEmpty($errors);
    }
}
