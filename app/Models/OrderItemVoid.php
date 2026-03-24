<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItemVoid extends Model
{
	use HasFactory;

	protected $table = 'order_item_voids';

	protected $fillable = [
		'order_id',
		'order_item_id',
		'menu_item_id',
		'qty',
		'prev_qty',
		'reason',
		'voided_by',
		'voided_by_role',
		'manager_pin_used'
	];

	public function order()
	{
		return $this->belongsTo(Order::class);
	}

	public function item()
	{
		return $this->belongsTo(OrderItem::class, 'order_item_id');
	}

	// Backwards-compatible accessor used elsewhere as `orderItem`
	public function orderItem()
	{
		return $this->belongsTo(OrderItem::class, 'order_item_id');
	}

	public function menuItem()
	{
		return $this->belongsTo(MenuItem::class, 'menu_item_id');
	}

	public function user()
	{
		return $this->belongsTo(User::class, 'voided_by');
	}
}
