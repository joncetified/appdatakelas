<?php

namespace App\Models;

use App\Models\Concerns\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSetting extends Model
{
    use HasAuditTrail;
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_name',
        'logo_path',
        'address',
        'manager_name',
        'contact_email',
        'contact_phone',
        'contact_whatsapp',
        'discord_webhook_url',
        'google_recaptcha_site_key',
        'google_recaptcha_secret_key',
    ];

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
