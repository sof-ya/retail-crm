<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer->name,
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->warehouse->name,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'completed_at' => $this->completed_at,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
