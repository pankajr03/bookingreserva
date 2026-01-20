<?php

namespace BookneticApp\Backend\Mobile\Services;

use BookneticApp\Backend\Mobile\Clients\FSCodeMobileAppClient;
use BookneticApp\Backend\Mobile\Exceptions\ApiException;
use BookneticApp\Backend\Mobile\Exceptions\InvalidSeatCountException;
use BookneticApp\Providers\FSCode\Clients\RequestDTOs\DTOs\Response\ApiResponse;

class SubscriptionService
{
    private FSCodeMobileAppClient $client;

    public function __construct(FSCodeMobileAppClient $client)
    {
        $this->client = $client;
    }

    /**
     * @return array
     */
    public function getActive(): array
    {
        $subscription = $this->client->getActiveSubscription();

        if (empty($subscription)) {
            return [];
        }

        $subscription['paymentMethodLabel'] = $this->getPaymentMethodLabel($subscription);

        return $subscription;
    }

    /**
     * TODO bu method-u structurlu data gondercek shekilde update etmeliyik. card, last4 kimi field-leri
     *  front-a gonderib orda qurmaliyiq
     */
    private function getPaymentMethodLabel(array $subscription): string
    {
        $paymentMethod = $subscription['paymentMethod'] ?? null;

        if (empty($paymentMethod) || !is_array($paymentMethod)) {
            return bkntc__('No payment method');
        }

        $type = $paymentMethod['type'] ?? '';

        if (!isset($paymentMethod[$type])) {
            return bkntc__('No payment method details');
        }

        $label = ucfirst(implode(' ', explode('_', $type)));

        if ($type === 'south_korea_local_card') {
            $label .= ' - ' . $paymentMethod[$type]['type'] . ' ****' . $paymentMethod[$type]['last4'];
        }

        if ($type === 'card') {
            $card = $paymentMethod['card'] ?? [];

            if (!empty($card)) {
                $label .= ' - '
                    . ($card['type'] ?? '')
                    . ' ****'
                    . ($card['last4'] ?? '')
                    . ' '
                    . str_pad($card['expiryMonth'] ?? '', 2, '0', STR_PAD_LEFT)
                    . '/'
                    . ($card['expiryYear'] ?? '')
                    . ' '
                    . ($card['cardholderName'] ?? '');
            }
        }

        return $label;
    }

    /**
     * @throws InvalidSeatCountException
     * @throws ApiException
     */
    public function createPaymentLink(int $planId, int $extraSeatCount): string
    {
        if ($extraSeatCount < 0) {
            throw new InvalidSeatCountException();
        }

        $response = $this->client->subscribe($planId, $extraSeatCount);

        $response = $response->getData();

        if (
            isset($response['url']) && filter_var($response['url'], FILTER_VALIDATE_URL)
        ) {
            return $response['url'];
        }

        throw new ApiException("Unable to create checkout url");
    }

    /**
     * @return void
     */
    public function cancelSubscription(): void
    {
        $this->client->unsubscribe();
    }

    /**
     * @return void
     */
    public function undoCancellation(): void
    {
        $this->client->undoCancellation();
    }

    /**
     * @param int $seatCount
     * @return ApiResponse
     */
    public function getPreview(int $seatCount): ApiResponse
    {
        return $this->client->previewSubscription($seatCount);
    }

    public function update(int $seatCount): void
    {
        $response = $this->client->updateSubscription($seatCount);

        if (!$response->getStatus()) {
            throw new \RuntimeException($response->getErrorMessage() ?? bkntc__('An error occurred while processing your request'));
        }
    }
}
