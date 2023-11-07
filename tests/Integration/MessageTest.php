<?php

use MarketforceInfo\SendGrid\Message;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MarketforceInfo\SendGrid\Message
 */
class MessageTest extends TestCase
{
    public function testSingleEmailContainsSinglePersonalization()
    {
        $mail = new Message();
        $this->assertEquals(1, $mail->getSendGridMail()->getPersonalizationCount());
        $mail->getSendGridMail()->addCc('cc@example.com');
        $this->assertEquals(1, $mail->getSendGridMail()->getPersonalizationCount());
        $mail->setTo('to@example.com')
            ->setFrom('from@example.com')
            ->setSubject('Example Subject')
            ->setTextBody('Example Text Body');
        $sendGridMail = $mail->buildMessage();
        $this->assertEquals(1, $sendGridMail->getPersonalizationCount());
    }
}
