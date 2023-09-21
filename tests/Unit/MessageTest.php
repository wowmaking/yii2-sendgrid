<?php

namespace MarketforceInfo\SendGrid\Tests\Unit;

use MarketforceInfo\SendGrid\Message;
use PHPUnit\Framework\TestCase;
use SendGrid\Mail\Mail;
use yii\mail\BaseMessage;
use yii\mail\MessageInterface;

/**
 * @covers \MarketforceInfo\SendGrid\Message
 */
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
            'cc_multiple' => ['cc', ['cc-user1@example.com', 'cc-user2@example.com']],
            'bcc_multiple' => ['bcc', ['bcc-user1@example.com', 'bcc-user2@example.com']],
        ];
    }

    public function testSettingArrayOfCcs()
    {
        $message = (new Message())
            ->setSubject('Foo Bar')
            ->setTo('user@example.com')
            ->setFrom('from@example.com')
            ->setTextBody('Some message')
            ->setCc(['cc1@example.com', 'cc2@example.com']);
        $sendGridMail = $message->buildMessage();
        self::assertCount(2, $sendGridMail->getPersonalization()->getCcs());
        self::assertEquals('cc1@example.com', $sendGridMail->getPersonalization()->getCcs()[0]->getEmail());
        self::assertEquals('cc2@example.com', $sendGridMail->getPersonalization()->getCcs()[1]->getEmail());
    }

    public function testSettingArrayOfBccs()
    {
        $message = (new Message())
            ->setSubject('Foo Bar')
            ->setTo('user@example.com')
            ->setFrom('from@example.com')
            ->setTextBody('Some message')
            ->setBcc(['bcc1@example.com', 'bcc2@example.com']);
        $sendGridMail = $message->buildMessage();
        self::assertCount(2, $sendGridMail->getPersonalization()->getBccs());
        self::assertEquals('bcc1@example.com', $sendGridMail->getPersonalization()->getBccs()[0]->getEmail());
        self::assertEquals('bcc2@example.com', $sendGridMail->getPersonalization()->getBccs()[1]->getEmail());
    }

    public function testValuesAreSetOnSendmailObject()
    {
        $message = (new Message())
            ->setSubject('Foo Bar')
            ->setTo('user@example.com')
            ->setFrom('from@example.com')
            ->setTextBody('Some message');
        $sendGridMail = $message->buildMessage();
        self::assertEquals('Foo Bar', $sendGridMail->getGlobalSubject()->getSubject());
        self::assertStringContainsString('user@example.com', $sendGridMail->getPersonalization()->getTos()[0]->getEmail());
        self::assertStringContainsString('from@example.com', $sendGridMail->getFrom()->getEmail());
        self::assertStringContainsString('Some message', $sendGridMail->getContents()[0]->getValue());
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

    public function testCanAlterMailSettings()
    {
        $message = new Message();
        $message->getMailSettings()->enableSandboxMode();

        self::assertSame($message->getSendGridMail()->getMailSettings(), $message->getMailSettings());
        self::assertTrue($message->getSendGridMail()->getMailSettings()->getSandboxMode()->getEnable());
    }

    public function testCanAlterTrackingSettings()
    {
        $message = new Message();
        $message->getTrackingSettings()->setClickTracking(true);

        self::assertSame($message->getSendGridMail()->getTrackingSettings(), $message->getTrackingSettings());
        self::assertTrue($message->getSendGridMail()->getTrackingSettings()->getClickTracking()->getEnable());
    }

    public function testCanAlterAsmSettings()
    {
        $groupId = 123;
        $groupsToDisplay = [123, 456, 789];

        $message = new Message();
        $message->getAsm()->setGroupId($groupId);
        $message->getAsm()->setGroupsToDisplay($groupsToDisplay);

        self::assertSame($message->getSendGridMail()->getAsm(), $message->getAsm());
        // sendgrid sdk is weird
        self::assertEquals($groupId, $message->getSendGridMail()->getAsm()->getGroupId()->getGroupId());
        self::assertEquals(
            $groupsToDisplay,
            $message->getSendGridMail()->getAsm()->getGroupsToDisplay()->getGroupsToDisplay()
        );
    }
}
