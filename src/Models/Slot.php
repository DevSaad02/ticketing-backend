<?php
namespace App\Models;
use Model;
/**
 * Table name for Paris ORM
 * @var string
 * @property int $id
 * @property int $system_id
 * @property int $parkig_id
 * @property int $user_id
 * @property string $status
 */

class Slot extends Model
{
     // Table name as a string
     public static $_table = 'slot';


     // Primary key column
     public static $_id_column = 'id';
}
