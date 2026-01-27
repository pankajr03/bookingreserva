<?php

namespace BookneticApp\Backend\Appointments\Mappers;

use BookneticApp\Backend\Appointments\Helpers\AppointmentSmartObject;
use BookneticApp\Providers\Helpers\Helper;

class AppointmentSmartObjectMapper
{
    public static function toArray(AppointmentSmartObject $appointmentInfo): array
    {
        $prices = $appointmentInfo->getPrices();

        $priceArray = [];

        foreach ($prices as $price) {
            $priceArray[] = [
                'name' => $price->name,
                'price' => $price->price,
                'formattedPrice' => Helper::price($price->price),
            ];
        }

        $discount = $appointmentInfo->getTotalAmount() - $appointmentInfo->getRealPaidAmount() - $appointmentInfo->getDueAmount();

        return [
            'id' => $appointmentInfo->getId(),
            'staff' => $appointmentInfo->getStaffInf()->name,
            'service' => $appointmentInfo->getServiceInf()->name,
            'startTime' => $appointmentInfo->getAppointmentInfo()->starts_at,
            'customer' => [
                'id' => $appointmentInfo->getCustomerInf()->id,
                'name' => $appointmentInfo->getCustomerInf()->full_name,
                'email' => $appointmentInfo->getCustomerInf()->email,
                'profileImage' => $appointmentInfo->getCustomerInf()->profile_image,
            ],
            'payment' => [
                'paymentMethod' => $appointmentInfo->getInfo()->payment_method,
                'paymentStatus' => $appointmentInfo->getInfo()->payment_status,
                'totalAmount' => $appointmentInfo->getTotalAmount(),
                'totalAmountFormatted' => Helper::price($appointmentInfo->getTotalAmount()),
                'paid' => $appointmentInfo->getRealPaidAmount(),
                'paidFormatted' => Helper::price($appointmentInfo->getRealPaidAmount()),
                'due' => $appointmentInfo->getDueAmount(),
                'dueFormatted' => Helper::price($appointmentInfo->getDueAmount()),
                'discount' => $discount,
                'discountFormatted' => Helper::price($discount),
                'prices' => $priceArray,
            ]
        ];
    }
}
