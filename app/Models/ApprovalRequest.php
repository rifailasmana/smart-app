<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
	use HasFactory;

	protected $table = 'approval_requests';

	protected $fillable = [
		'warung_id',
		'type',
		'payload',
		'status',
		'requested_by',
		'processed_by',
		'reason',
		'notes',
	];

	protected $casts = [
		'payload' => 'array',
	];

	public function warung()
	{
		return $this->belongsTo(Warung::class, 'warung_id');
	}

	public function requester()
	{
		return $this->belongsTo(User::class, 'requested_by');
	}

	public function processor()
	{
		return $this->belongsTo(User::class, 'processed_by');
	}
}
