<?php
namespace App\Models;
use Model;
/**
 * Table name for Paris ORM
 * @var string
 * @property int $id
 * @property int $user_id
 * @property string $message
 * @property datetime $created_at
 */

class Feedback extends Model
{
     // Table name as a string
     public static $_table = 'feedback';


     // Primary key column
     public static $_id_column = 'id';
}
