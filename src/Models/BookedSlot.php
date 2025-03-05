<?php
namespace App\Models;
use Model;
/**
 * Table name for Paris ORM
 * @var string
 * @property int $id
 * @property int $slot_id
 * @property date $date
 * @property time $start_time
 * @property time $end_time
 */

class Booking extends Model
{
     // Table name as a string
     public static $_table = 'booked_slot';


     // Primary key column
     public static $_id_column = 'id';
}
