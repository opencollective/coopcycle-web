<?php

namespace Tests\AppBundle\Domain\Order\Handler;

use AppBundle\Domain\Order\Command\CreatePaymentIntent;
use AppBundle\Domain\Order\Handler\CreatePaymentIntentHandler;
use AppBundle\Entity\StripePayment;
use AppBundle\Service\SettingsManager;
use AppBundle\Service\StripeManager;
use AppBundle\Sylius\Order\OrderInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\NullLogger;
use SimpleBus\Message\Recorder\RecordsMessages;
use Stripe;
use Sylius\Bundle\OrderBundle\NumberAssigner\OrderNumberAssignerInterface;
use Sylius\Component\Payment\Model\PaymentInterface;
use Tests\AppBundle\StripeTrait;

class CreatePaymentIntentHandlerTest extends TestCase
{
    use StripeTrait {
        setUp as setUpStripe;
    }

    private $eventRecorder;
    private $orderNumberAssigner;

    private $handler;

    public function setUp(): void
    {
        $this->setUpStripe();

        $this->eventRecorder = $this->prophesize(RecordsMessages::class);
        $this->orderNumberAssigner = $this->prophesize(OrderNumberAssignerInterface::class);
        $this->stripeManager = $this->prophesize(StripeManager::class);

        $settingsManager = $this->prophesize(SettingsManager::class);

        $settingsManager
            ->isStripeLivemode()
            ->willReturn(false);
        $settingsManager
            ->get('stripe_secret_key')
            ->willReturn(self::$stripeApiKey);

        $stripeManager = new StripeManager(
            $settingsManager->reveal(),
            new NullLogger()
        );

        $this->handler = new CreatePaymentIntentHandler(
            $this->eventRecorder->reveal(),
            $this->orderNumberAssigner->reveal(),
            $stripeManager
        );
    }

    public function testManualCapture()
    {
        $stripePayment = new StripePayment();
        $stripePayment->setAmount(900);
        $stripePayment->setCurrencyCode('EUR');

        $order = $this->prophesize(OrderInterface::class);
        $order
            ->getId()
            ->willReturn(1);
        $order
            ->getNumber()
            ->willReturn('000001');
        $order
            ->getRestaurant()
            ->willReturn(null);
        $order
            ->getLastPayment(/* PaymentInterface::STATE_CART */)
            ->willReturn($stripePayment);

        $stripePayment->setOrder($order->reveal());

        $this->orderNumberAssigner
            ->assignNumber($order)
            ->shouldBeCalled();

        $paymentMethod = Stripe\PaymentMethod::create([
            'type' => 'card',
            'card' => [
                'number' => '4242424242424242',
                'exp_month' => 9,
                'exp_year' => 2020,
                'cvc' => '314'
            ]
        ]);

        $this->shouldSendStripeRequest('POST', '/v1/payment_intents', [
            'amount' => 900,
            'currency' => 'eur',
            'description' => 'Order 000001',
            'payment_method' => $paymentMethod->id,
            'confirmation_method' => 'manual',
            'confirm' => 'true',
            'capture_method' => 'manual'
        ]);

        $command = new CreatePaymentIntent($order->reveal(), $paymentMethod->id, false);

        call_user_func_array($this->handler, [$command]);

        $this->assertEquals($paymentMethod->id, $stripePayment->getPaymentMethod());
        $this->assertRegExp('/^pi_[0-9A-Za-z]+$/', $stripePayment->getPaymentIntent());
    }
}
