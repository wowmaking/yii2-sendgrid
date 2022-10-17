<?php

namespace MarketforceInfo\SendGrid\Tests\Unit;

use MarketforceInfo\SendGrid\Message;
use PHPUnit\Framework\TestCase;
use SendGrid\Mail\Mail;
use yii\mail\BaseMessage;
use yii\mail\MessageInterface;

class MessageTest extends TestCase
{
    public function testImplementsYiiMessage()
    {
        $instance = new Message();
        self::assertInstanceOf(BaseMessage::class, $instance);
        self::assertInstanceOf(MessageInterface::class, $instance);
    }

    public function testGetSendGridMailReturnsSameInstance()
    {
        $message = new Message();
        self::assertEquals(
            spl_object_id($message->getSendGridMail()),
            spl_object_id($message->getSendGridMail())
        );
    }

    public function testAddingPersonalization()
    {
        $message = new Message();
        self::assertEmpty($message->personalizations);
        $message->addPersonalization('{username}');
        self::assertEquals('{username}', current($message->personalizations));
    }

    public function testAddingSubstitution()
    {
        $message = new Message();
        self::assertEmpty($message->substitutions);
        $message->addSubstitution('{username}', 'Foo Bar');
        self::assertArrayHasKey('{username}', $message->substitutions);
        self::assertEquals('Foo Bar', $message->substitutions['{username}']);
    }

    public function testAddingHeader()
    {
        $message = new Message();
        $message->addHeader('X-Foo', 'Bar Baz');
        self::assertIsArray($message->headers);
        self::assertArrayHasKey('X-Foo', $message->headers);
        self::assertEquals('Bar Baz', $message->headers['X-Foo']);
    }

    public function testSettingTemplateId()
    {
        $message = new Message();
        $message->setTemplateId('template-id');
        self::assertEquals('template-id', $message->templateId);
    }

    public function testAddingSection()
    {
        $message = new Message();
        $message->addSection('section1', 'SectionName');
        self::assertIsArray($message->sections);
        self::assertArrayHasKey('section1', $message->sections);
        self::assertEquals('SectionName', $message->sections['section1']);
    }

    /**
     * @dataProvider settersGettersProvider
     */
    public function testSettersGetters(string $attribute, $value)
    {
        $message = new Message();
        $setMethod = 'set' . ucfirst($attribute);
        self::assertSame($message, $message->{$setMethod}($value));
        $getMethod = 'get' . ucfirst($attribute);
        self::assertSame($value, $message->{$getMethod}());
    }

    public function settersGettersProvider(): array
    {
        return [
            'to' => ['to', 'user@example.com'],
            'from' => ['from', 'sender@example.com'],
            'subject' => ['subject', 'Subject Line'],
            'charset' => ['charset', 'utf-8'],
            'reply' => ['replyTo', 'reply@example.com'],
            'cc' => ['cc', 'cc-user@example.com'],
            'bcc' => ['bcc', 'bcc-user@example.com'],
        ];
    }

    public function testToString()
    {
        $message = new Message();
        $message->setSubject('Foo Bar')
            ->setTo('user@example.com')
            ->setFrom('from@example.com')
            ->setTextBody('Some message');
        $messageString = (string)$message;
        self::assertStringContainsString('Foo Bar', $messageString);
        self::assertStringContainsString('user@example.com', $messageString);
        self::assertStringContainsString('from@example.com', $messageString);
        self::assertStringContainsString('Some message', $messageString);
        self::assertIsObject(json_decode($messageString, false, 512, JSON_THROW_ON_ERROR));
    }

    public function testBuild()
    {
        $message = new Message();
        $message->setSubject('Foo Bar')
            ->setTo('user@example.com')
            ->setFrom('from@example.com')
            ->setTextBody('Some message');
        $mail = $message->buildMessage();
        self::assertInstanceOf(Mail::class, $mail);
        self::assertEquals('Foo Bar', $mail->getGlobalSubject()->getSubject());
        self::assertEquals('from@example.com', $mail->getFrom()->getEmail());
        self::assertEquals('Some message', $mail->getContents()[0]->getValue());
    }
}
