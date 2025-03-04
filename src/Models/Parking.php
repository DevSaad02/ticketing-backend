<?php
namespace App\Models;
use Model;
/**
 * Table name for Paris ORM
 * @var string
 * @property int $id
 * @property string $place
 * @property string $vehicle_type
 * @property string $landmark
 * @property string $address
 */

class Parking extends Model
{
     // Table name as a string
     public static $_table = 'parking';


     // Primary key column
     public static $_id_column = 'id';
}
