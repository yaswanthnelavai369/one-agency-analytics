<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = ['agency_id', 'client_id', 'user_id', 'anomaly_id', 'channel', 'recipient', 'status', 'error'];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function anomaly(): BelongsTo
    {
        return $this->belongsTo(Anomaly::class);
    }
}
