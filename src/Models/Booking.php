<?php
namespace App\Models;
use Model;
/**
 * Table name for Paris ORM
 * @var string
 * @property int $id
 * @property int $user_id
 * @property int $slot_id
 * @property string $vehicle_registration_number
 * @property string $vehicle_type
 * @property string $vehicle_name
 * @property string $vehicle_owner
 * @property string $contact_number
 * @property date $date
 * @property time $start_time
 * @property time $end_time
 * @property string $status
 */

class Booking extends Model
{
     // Table name as a string
     public static $_table = 'booking';


     // Primary key column
     public static $_id_column = 'id';
}
