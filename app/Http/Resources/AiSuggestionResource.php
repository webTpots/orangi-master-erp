<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiSuggestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'suggestion_type' => $this->suggestion_type,
            'type_label'      => $this->type_label,
            'title'           => $this->title,
            'description'     => $this->description,
            'confidence'      => (float) $this->confidence,
            'priority'        => $this->priority,
            'status'          => $this->status,
            'status_label'    => $this->status_label,
            'entity_type'     => $this->entity_type,
            'entity_id'       => $this->entity_id,
            'action_data'     => $this->action_data,
            'expires_at'      => $this->expires_at?->toIso8601String(),
            'accepted_at'     => $this->accepted_at?->toIso8601String(),
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
