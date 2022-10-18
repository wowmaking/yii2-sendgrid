<?php

namespace MarketforceInfo\SendGrid\Tests\Unit;

use MarketforceInfo\SendGrid\Mailer;
use PHPUnit\Framework\TestCase;
use yii\base\InvalidConfigException;
use yii\mail\BaseMailer;
use yii\mail\MailerInterface;

/**
 * @covers \MarketforceInfo\SendGrid\Mailer
 */
class MailerTest extends TestCase
{
    public function testImplementsYiiMailer()
    {
        $instance = new Mailer([]);
        self::assertInstanceOf(BaseMailer::class, $instance);
        self::assertInstanceOf(MailerInterface::class, $instance);
    }

    public function testDefaultFileTransportPath()
    {
        self::assertEquals('@runtime/mail', (new Mailer())->fileTransportPath);
    }

    public function testCanNotInstantiateSendGridWithoutKey()
    {
        $this->expectException(InvalidConfigException::class);
        (new Mailer())->getSendGrid();
    }

    public function testGetSendGridIsSingleInstance()
    {
        $mailer = new Mailer();
        $mailer->apiKey = 'ABC123';
        self::assertEquals(spl_object_id($mailer->getSendGrid()), spl_object_id($mailer->getSendGrid()));
    }

    public function testAddingRawResponse()
    {
        $mailer = new Mailer();
        self::assertEmpty($mailer->getRawResponses());
        $mailer->addRawResponse([
            'status' => 200,
            'message' => '',
        ]);
        self::assertNotEmpty($mailer->getRawResponses());
    }

    public function testAddingErrors()
    {
        $mailer = new Mailer();
        self::assertEmpty($mailer->getErrors());
        $mailer->addError('Error Message');
        self::assertNotEmpty($mailer->getErrors());
        self::assertEquals('Error Message', current($mailer->getErrors()));
    }

    /**
     * @dataProvider errorCodeParsingProvider
     */
    public function testErrorCodeParsing(int $statusCode, string $messageContains)
    {
        $mailer = new Mailer();
        self::assertStringContainsString($messageContains, $mailer->parseErrorCode($statusCode));
    }

    public function errorCodeParsingProvider(): array
    {
        return [
            'ok' => [200, 'message is valid'],
            'bad-request' => [400, 'Bad Request'],
            'authorization' => [401, 'API Key is probably missing'],
            'json' => [413, 'JSON payload you'],
            'requests' => [429, 'number of requests'],
            'server' => [500, 'error occurred'],
            'maintenance' => [503, 'API is not available'],
            'unknown' => [600, 'unknown error'],
        ];
    }
}
