<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvertisementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'plan' => $this->bankPlan?->title,
            'bank_plan' => $this->whenLoaded('bankPlan', fn () => $this->bankPlan ? [
                'id' => $this->bankPlan->id,
                'title' => $this->bankPlan->title,
                'interest_rate' => (float) $this->bankPlan->interest_rate,
            ] : null),
            'bank' => $this->bank?->name,
            'bank_id' => $this->bank_id,
            'amount' => $this->loan_amount,
            'loan_amount' => $this->loan_amount,
            'price' => $this->assignment_price,
            'assignment_price' => $this->assignment_price,
            'fee' => (float) $this->profit_rate,
            'profit_rate' => (float) $this->profit_rate,
            'installment_count' => $this->installment_count,
            'city' => $this->location?->name,
            'province' => $this->location?->parent?->name,
            'description' => $this->description,
            'rejection_reason' => $this->rejection_reason,
            'views_count' => $this->views_count,
            'advertiser_mobile' => $this->when(
                $request->user('sanctum')?->is_verified === true && $this->user?->show_phone_publicly,
                fn () => $this->user->mobile,
            ),
            'status' => $this->status,
            'time' => $this->created_at?->locale('fa')->diffForHumans(),
        ];
    }
}