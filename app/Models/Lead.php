<?php

namespace App\Models;

use App\Traits\HasModelPermissions;
use App\Traits\ScopedToOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lead extends Model
{
    use HasModelPermissions;
    use HasFactory;
    use SoftDeletes;

    public static function additionalPermissions(): array
    {
        return ['view_team'];
    }

    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Basic Lead Information
        |--------------------------------------------------------------------------
        */

        'name',
        'phone',
        'email',
        'message',
        'source_website',
        'external_id',

        'status',
        'type',

        /*
        |--------------------------------------------------------------------------
        | Assignment / CRM
        |--------------------------------------------------------------------------
        */

        'assigned_to',
        'follow_up_date',

        'visit_status',
        'visit_date',

        'sub_response_id',

        'lead_source',

        /*
        |--------------------------------------------------------------------------
        | Property Requirements
        |--------------------------------------------------------------------------
        */

        'inventory',

        'budget_min',
        'budget_max',

        'remarks',

        /*
        |--------------------------------------------------------------------------
        | Source Page
        |--------------------------------------------------------------------------
        */

        'source_page_id',
        'source_page_name',

        /*
        |--------------------------------------------------------------------------
        | Source Form
        |--------------------------------------------------------------------------
        */

        'source_form_id',
        'source_form_name',

        /*
        |--------------------------------------------------------------------------
        | Campaign
        |--------------------------------------------------------------------------
        */

        'campaign_id',
        'campaign_name',

        /*
        |--------------------------------------------------------------------------
        | Ad Set
        |--------------------------------------------------------------------------
        */

        'adset_id',
        'adset_name',

        /*
        |--------------------------------------------------------------------------
        | Ad
        |--------------------------------------------------------------------------
        */

        'ad_id',
        'ad_name',

        /*
        |--------------------------------------------------------------------------
        | Buyer Qualification
        |--------------------------------------------------------------------------
        */

        'purchase_timeline',
        'site_visit_preference',
        'funding_method',

        /*
        |--------------------------------------------------------------------------
        | Original Source Submission Time
        |--------------------------------------------------------------------------
        */

        'source_submitted_at',
    ];


    protected $casts = [
        'follow_up_date' => 'datetime',
        'visit_date' => 'date',
        'source_submitted_at' => 'datetime',

        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
    ];


    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }


    public function assignedUser()
    {
        return $this->belongsTo(
            \App\Models\User::class,
            'assigned_to'
        );
    }


    public function leadResponses()
    {
        return $this->hasMany(
            LeadResponse::class,
            'lead_id'
        );
    }


    public function project()
    {
        return $this->belongsTo(Project::class);
    }


    public function createdBy()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


    public function updatedBy()
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }


    public function subResponse()
    {
        return $this->belongsTo(SubResponse::class);
    }
	//added
	
  
	
	public function getPageKeyAttribute(): ?string
	{
		return $this->attributes['source_page_name'] ?? null;
	}

	public function getPageNameAttribute(): ?string
	{
		$pageKey = trim($this->attributes['source_page_name'] ?? '');

		if ($pageKey === '') {
			return null;
		}

		if (self::isFacebookSourceWebsite($this->source_website)) {
			return $pageKey;
		}

		// FE_GNW is the short form of Future Estate
		if (stripos($pageKey, 'FE_GNW_') !== false) {
			return 'Future Estate';
		}

		// Format remaining page names
		$pageName = str_replace(['_', '-'], ' ', $pageKey);

		if ($pageName === $pageKey) {
			return $pageKey;
		}

		return ucwords(strtolower($pageName));
	}

	public static function resolveSourcePageName(
		?string $message,
		?string $sourceWebsite,
		?string $fallback = null
	): ?string {
		if (!self::isFacebookSourceWebsite($sourceWebsite) || !$message) {
			return $fallback;
		}

		if (!preg_match('/(?:^|\|)\s*Form\s*:\s*([^|\r\n]+)/i', $message, $matches)) {
			return $fallback;
		}

		$formName = trim($matches[1]);

		return $formName !== '' ? $formName : $fallback;
	}

	public static function isFacebookSourceWebsite(?string $sourceWebsite): bool
	{
		if (!$sourceWebsite) {
			return false;
		}

		$sourceWebsite = trim($sourceWebsite);
		$host = parse_url($sourceWebsite, PHP_URL_HOST);

		if (!$host) {
			$host = parse_url('https://' . ltrim($sourceWebsite, '/'), PHP_URL_HOST);
		}

		$host = strtolower(rtrim((string) $host, '.'));

		return $host === 'facebook.com' || str_ends_with($host, '.facebook.com');
	}



}