<?php

namespace BookneticApp\Backend\Mobile\Clients;

use BookneticApp\Backend\Mobile\Clients\Models\MobileAppSeatsResponse;
use BookneticApp\Providers\FSCode\Clients\FSCodeAPIClient;
use RuntimeException;
use BookneticApp\Providers\FSCode\Clients\RequestDTOs\DTOs\Response\ApiResponse;

class FSCodeMobileAppClient
{
    private FSCodeAPIClient $client;

    public function __construct(FSCodeAPIClient $client)
    {
        $this->client = $client;
    }

    public function getPlans(): ApiResponse
    {
        return $this->request('plans');
    }

    public function getPlan(int $planId): ApiResponse
    {
        return $this->request(sprintf('plans/%d', $planId));
    }

    public function subscribe(int $planId, int $extraSeatCount): ApiResponse
    {
        return $this->request('subscription', 'POST', [
            'planId' => $planId,
            'extraSeatCount' => $extraSeatCount,
        ]);
    }

    public function unsubscribe()
    {
        $this->request('subscription', 'DELETE');
    }

    public function undoCancellation()
    {
        $this->request('subscription/undo-cancel', 'POST');
    }

    public function getActiveSubscription(): array
    {
        try {
            $response = $this->request('subscription');
        } catch (RuntimeException $e) {
            return [];
        }

        return $response->getData() ?? [];
    }

    public function updateSubscription(int $seatCount): ApiResponse
    {
        return $this->request('subscription', 'PATCH', ['extraSeatCount' => $seatCount]);
    }

    public function previewSubscription(int $seatCount): ApiResponse
    {
        return $this->request('subscription/preview', 'POST', ['extraSeatCount' => $seatCount]);
    }

    public function getSeats(): MobileAppSeatsResponse
    {
        $response = $this->request('seats');
        $data = $response->getData();

        return new MobileAppSeatsResponse(
            (array) ($data['assignedSeats'] ?? []),
            (int) ($data['availableSeats'] ?? 0)
        );
    }

    public function getSeatsByUsername(string $username): MobileAppSeatsResponse
    {
        $response = $this->request("seats", 'GET', ['username' => $username]);
        $data = $response->getData();

        return new MobileAppSeatsResponse(
            (array) ($data['assignedSeats'] ?? []),
            (int) ($data['availableSeats'] ?? 0)
        );
    }

    public function assignSeat(string $username)
    {
        return $this->request('seats', 'POST', ['username' => $username]);
    }

    public function logoutSeat(int $id): void
    {
        $this->request(sprintf('seats/%d/logout', $id), 'POST');
    }

    public function unassignSeat(int $id): void
    {
        $this->request(sprintf('seats/%d', $id), 'DELETE');
    }

    private function request(string $endpoint, string $method = 'GET', array $data = []): ApiResponse
    {
        return $this->client->requestNew(sprintf('mobile-app/admin/%s', $endpoint), $method, $data, 'v1');
    }
}
