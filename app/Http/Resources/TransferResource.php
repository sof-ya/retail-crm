<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_warehouse_id' => $this->from_warehouse_id,
            'from_warehouse_name' => $this->fromWarehouse->name,
            'to_warehouse_id' => $this->to_warehouse_id,
            'to_warehouse_name' => $this->toWarehouse->name,
            'created_at' => $this->created_at,
            'items' => TransferItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
