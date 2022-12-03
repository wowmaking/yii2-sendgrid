<?php

namespace MarketforceInfo\SendGrid\Tests\Integration;

use MarketforceInfo\SendGrid\Mailer;
use MarketforceInfo\SendGrid\Message;
use MarketforceInfo\SendGrid\Tests\MockClient;
use PHPUnit\Framework\TestCase;
use SendGrid\Mail\Mail;

/**
 * @covers \MarketforceInfo\SendGrid\Mailer
 * @covers \MarketforceInfo\SendGrid\Message
 */
class SendTest extends TestCase
{
    private Mailer $mailer;
    private MockClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new MockClient();
        $this->mailer = new Mailer();
        $this->mailer->apiKey = 'ABC123';
        $this->mailer->getSendGrid()->client = $this->client;
    }

    public function testMissingApiKey()
    {
        $message = $this->createMock(Message::class);
        $message->method('buildMessage')->willReturn(new Mail());

        $mailer = new Mailer();
        $mailer->send($message);

        self::assertStringContainsString('API Key', current($mailer->getErrors()));
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
        $this->client->addResponse(500, 'Internal Error');

        self::assertFalse($this->mailer->send($message));

        $errors = $this->mailer->getErrors();
        self::assertNotEmpty($errors);
        self::assertStringContainsString('An error occurred', current($errors));
    }

    public function testSendsSuccessfully()
    {
        $message = $this->createStub(Message::class);
        $message->method('buildMessage')->willReturn(new Mail());
        $this->client->addSuccessfulResponse();

        self::assertTrue($this->mailer->send($message));

        $errors = $this->mailer->getErrors();
        self::assertEmpty($errors);
    }

    public function testMailSettingsApplied()
    {
        $mail = (new Message())->getSendGridMail();
        $mail->getMailSettings()->enableSandboxMode();
        $this->client->addSuccessfulResponse();

        self::assertTrue($this->mailer->send($this->message($mail)));

        $requests = $this->client->getRequestHistory();
        self::assertCount(1, $requests);
        self::assertStringContainsString('mail/send', $requests[0]['url']);
        self::assertStringContainsString('"mail_settings":{"sandbox_mode":{"enable":true}}', $requests[0]['body']);
    }

    public function testTrackingSettingsApplied()
    {
        $mail = (new Message())->getSendGridMail();
        $mail->getTrackingSettings()->setClickTracking(true);
        $this->client->addSuccessfulResponse();

        self::assertTrue($this->mailer->send($this->message($mail)));

        $requests = $this->client->getRequestHistory();
        self::assertCount(1, $requests);
        self::assertStringContainsString('mail/send', $requests[0]['url']);
        self::assertStringContainsString('"tracking_settings":{"click_tracking":{"enable":true}}', $requests[0]['body']);
    }

    /**
     * Bypasses the normal validation to allow shorter tests
     */
    private function message(Mail $mail): Message
    {
        $message = $this->createStub(Message::class);
        $message->method('buildMessage')->willReturn($mail);
        return $message;
    }
}
