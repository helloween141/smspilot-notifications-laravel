<?php

namespace AvtoDev\SmsPilotNotifications\Tests;

use AvtoDev\SmsPilotNotifications\ApiClient\Responses\MessageSentResponse;
use AvtoDev\SmsPilotNotifications\Exceptions\MissingNotificationRouteException;
use AvtoDev\SmsPilotNotifications\Messages\SmsPilotMessage;
use AvtoDev\SmsPilotNotifications\SmsPilotChannel;
use AvtoDev\SmsPilotNotifications\Tests\ApiClient\ApiClientMock;
use AvtoDev\SmsPilotNotifications\Tests\Stubs\Notifiable;
use AvtoDev\SmsPilotNotifications\Tests\Stubs\NotifiableStub;
use AvtoDev\SmsPilotNotifications\Tests\Stubs\Notification;
use AvtoDev\SmsPilotNotifications\Tests\Stubs\NotificationStub;
use InvalidArgumentException;
use Mockery as m;

/**
 * Class SmsPilotChannelTest.
 */
class SmsPilotChannelTest extends AbstractTestCase
{
    /**
     * Test it can send a notification.
     *
     * @return void
     */
    public function testNotificationSending()
    {
        $notifiable   = new NotifiableStub;
        $notification = new NotificationStub;
        $api_client   = new ApiClientMock('foo', 'bar');
        $channel      = new SmsPilotChannel($api_client);

        /** @var MessageSentResponse $response */
        $response = $channel->send($notifiable, $notification);

        $this->assertInstanceOf(MessageSentResponse::class, $response);
        $this->assertEquals($notifiable->routeNotificationForSmsPilot(), $response->getSentMessages()[0]->getPhone());
    }

    /**
     * Test it throws an exception when it could not send the notification.
     *
     * @return void
     */
    public function testNotificationExceptionOnSenderWithoutRoute()
    {
        $this->expectException(MissingNotificationRouteException::class);
        $this->expectExceptionMessageRegExp('~Missing notification route~i');

        $api_client = new ApiClientMock('foo', 'bar');
        $channel    = new SmsPilotChannel($api_client);

        $channel->send(new NotifiableStub, new Notification);
    }

    /**
     * Test it throws an exception when it try to send notification on object with invalid notification route method.
     *
     * @return void
     */
    public function testNotificationExceptionOnNotificationInvalidRoute()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('~must returns object with instance~i');

        $api_client = new ApiClientMock('foo', 'bar');
        $channel    = new SmsPilotChannel($api_client);

        $notification = m::mock(sprintf('%s[%s]', NotificationStub::class, $what = 'toSmsPilot'));
        $notification
            ->shouldReceive($what)
            ->once()
            ->andReturnUsing(function () {
                return new \stdClass;
            });

        $channel->send(new NotifiableStub, $notification);
    }

    /**
     * Test it returns null when it send notification on "notifiable" without route.
     *
     * @return void
     */
    public function testNotificationReturnsNullOnNotifiableWithoutRoute()
    {
        $api_client = new ApiClientMock('foo', 'bar');
        $channel    = new SmsPilotChannel($api_client);

        $this->assertNull($channel->send(new Notifiable, new NotificationStub));
    }

    /**
     * Test 'to' field overwriting, when 'toSmsPilot' method set it.
     *
     * @return void
     */
    public function testNotificationReceiverOverwriting()
    {
        $to           = '78887778877';
        $notifiable   = new NotifiableStub;
        $notification = m::mock(sprintf('%s[%s]', NotificationStub::class, $what = 'toSmsPilot'));
        $notification
            ->shouldReceive($what)
            ->once()
            ->andReturnUsing(function () use (&$to) {
                return SmsPilotMessage::create()
                    ->to($to)
                    ->from('Devil')
                    ->content('Some content');
            });

        $api_client = new ApiClientMock('foo', 'bar');
        $channel    = new SmsPilotChannel($api_client);

        /** @var MessageSentResponse $response */
        $response = $channel->send($notifiable, $notification);

        $this->assertEquals($to, $response->getSentMessages()[0]->getPhone());
    }
}
